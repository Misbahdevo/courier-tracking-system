<?php
/**
 * Rider Dashboard
 * Courier & Parcel Tracking System
 */

$page_title = "Rider Dashboard - SwiftPost";
$path_prefix = "../";
require_once $path_prefix . 'includes/header.php';

// Guard rider access
guard_rider();

$rider_id = $_SESSION['rider_id'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['parcel_id'])) {
    $parcel_id = intval($_POST['parcel_id']);
    $next_status = sanitize($conn, $_POST['action']);
    
    // Verify parcel belongs to this rider and get current status
    $check_q = "SELECT status FROM parcels WHERE id = ? AND rider_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $check_q);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $parcel_id, $rider_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        
        if ($parcel = mysqli_fetch_assoc($res)) {
            $current_status = $parcel['status'];
            
            // Validate sequence: Assigned -> Picked Up -> In Transit -> Delivered
            $valid_transition = false;
            if ($current_status === 'Assigned' && $next_status === 'Picked Up') {
                $valid_transition = true;
            } elseif ($current_status === 'Picked Up' && $next_status === 'In Transit') {
                $valid_transition = true;
            } elseif ($current_status === 'In Transit' && $next_status === 'Delivered') {
                $valid_transition = true;
            }
            
            if ($valid_transition) {
                // Begin Transaction
                mysqli_begin_transaction($conn);
                try {
                    // Update status
                    $update_q = "UPDATE parcels SET status = ? WHERE id = ? AND rider_id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_q);
                    mysqli_stmt_bind_param($update_stmt, "sii", $next_status, $parcel_id, $rider_id);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                    
                    // Insert status history
                    $history_q = "INSERT INTO parcel_status_history (parcel_id, status) VALUES (?, ?)";
                    $history_stmt = mysqli_prepare($conn, $history_q);
                    mysqli_stmt_bind_param($history_stmt, "is", $parcel_id, $next_status);
                    mysqli_stmt_execute($history_stmt);
                    mysqli_stmt_close($history_stmt);
                    
                    mysqli_commit($conn);
                    set_flash_message('success', "Parcel status updated to '$next_status' successfully.");
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    set_flash_message('danger', "Failed to update status. Database error.");
                }
            } else {
                set_flash_message('danger', "Invalid status transition.");
            }
        } else {
            set_flash_message('danger', "Parcel not found or not assigned to you.");
        }
        mysqli_stmt_close($stmt);
    }
    
    // Redirect to prevent re-submission
    header("Location: dashboard.php");
    exit();
}

// Query assigned parcels
$query = "SELECT * FROM parcels WHERE rider_id = ? ORDER BY field(status, 'Assigned', 'Picked Up', 'In Transit', 'Delivered'), created_at DESC";
$parcels = [];
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $rider_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $parcels[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="row mb-4 align-items-center">
    <div class="col">
        <h2 class="fw-bold mb-0">Rider Deliveries</h2>
        <p class="text-muted mb-0">Update and manage your assigned deliveries.</p>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($parcels)): ?>
        <div class="col-12">
            <div class="card glass-card border-0 py-5 text-center">
                <div class="card-body">
                    <i class="fa-solid fa-motorcycle fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No Deliveries Assigned</h4>
                    <p class="text-muted">You currently do not have any parcels assigned for delivery.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($parcels as $p): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card glass-card h-100 border-0">
                    <div class="card-header bg-white border-0 pt-3 px-3 d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-indigo"><?php echo htmlspecialchars($p['tracking_id']); ?></span>
                        <span class="status-badge <?php echo get_badge_class($p['status']); ?>">
                            <?php echo htmlspecialchars($p['status']); ?>
                        </span>
                    </div>
                    
                    <div class="card-body px-3 py-2">
                        <div class="mb-2">
                            <small class="text-muted text-uppercase fw-semibold d-block">Sender Details</small>
                            <span class="fw-semibold"><?php echo htmlspecialchars($p['sender_name']); ?></span> 
                            <small class="text-muted">(<?php echo htmlspecialchars($p['sender_phone']); ?>)</small>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-semibold d-block">Receiver Details</small>
                            <span class="fw-semibold"><?php echo htmlspecialchars($p['receiver_name']); ?></span>
                            <small class="text-muted">(<?php echo htmlspecialchars($p['receiver_phone']); ?>)</small>
                        </div>
                        <div class="p-2 bg-light rounded mb-3">
                            <small class="text-muted text-uppercase fw-semibold d-block"><i class="fa-solid fa-location-dot me-1"></i> Delivery Address</small>
                            <span class="small d-inline-block text-truncate w-100" title="<?php echo htmlspecialchars($p['delivery_address']); ?>">
                                <?php echo htmlspecialchars($p['delivery_address']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-white border-0 pb-3 px-3 d-flex justify-content-between align-items-center gap-2">
                        <a href="detail.php?id=<?php echo $p['id']; ?>" class="btn btn-outline-secondary btn-sm flex-grow-1">
                            <i class="fa-solid fa-info-circle me-1"></i> Details
                        </a>
                        
                        <?php if ($p['status'] !== 'Delivered'): ?>
                            <form action="dashboard.php" method="POST" class="flex-grow-1 m-0">
                                <input type="hidden" name="parcel_id" value="<?php echo $p['id']; ?>">
                                <?php if ($p['status'] === 'Assigned'): ?>
                                    <input type="hidden" name="action" value="Picked Up">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fa-solid fa-box-open me-1"></i> Pick Up
                                    </button>
                                <?php elseif ($p['status'] === 'Picked Up'): ?>
                                    <input type="hidden" name="action" value="In Transit">
                                    <button type="submit" class="btn btn-warning btn-sm w-100 text-white">
                                        <i class="fa-solid fa-truck-fast me-1"></i> Start Transit
                                    </button>
                                <?php elseif ($p['status'] === 'In Transit'): ?>
                                    <input type="hidden" name="action" value="Delivered">
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="fa-solid fa-circle-check me-1"></i> Deliver
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-success btn-sm disabled flex-grow-1">
                                <i class="fa-solid fa-check-double me-1"></i> Completed
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php 
require_once $path_prefix . 'includes/footer.php'; 
?>
