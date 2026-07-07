<?php
/**
 * Rider Delivery Detail
 * Courier & Parcel Tracking System
 */

$page_title = "Delivery Details - SwiftPost";
$path_prefix = "../";
require_once $path_prefix . 'includes/header.php';

// Guard rider access
guard_rider();

$rider_id = $_SESSION['rider_id'];

if (!isset($_GET['id'])) {
    set_flash_message('danger', 'No parcel specified.');
    header("Location: dashboard.php");
    exit();
}

$parcel_id = intval($_GET['id']);

// Fetch parcel details and verify rider
$query = "SELECT * FROM parcels WHERE id = ? AND rider_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $query);
$parcel = null;

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $parcel_id, $rider_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $parcel = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$parcel) {
    set_flash_message('danger', 'Unauthorized access or parcel not found.');
    header("Location: dashboard.php");
    exit();
}
?>

<div class="row mb-4">
    <div class="col">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        
        <div class="card glass-card border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 text-muted text-uppercase small fw-bold">Delivery Job Details</h5>
                    <h3 class="mb-0 fw-bold text-indigo"><?php echo htmlspecialchars($parcel['tracking_id']); ?></h3>
                </div>
                <div>
                    <span class="status-badge <?php echo get_badge_class($parcel['status']); ?> fs-6 py-2 px-3">
                        <?php echo htmlspecialchars($parcel['status']); ?>
                    </span>
                </div>
            </div>
            
            <hr class="mx-4 my-2 text-muted">
            
            <div class="card-body p-4">
                
                <!-- Package Details -->
                <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-box me-1"></i> Package Specifications</h5>
                <div class="row g-3 mb-4 bg-light p-3 rounded-3">
                    <div class="col-sm-6 col-md-4">
                        <small class="text-muted d-block">Type</small>
                        <span class="fw-semibold"><?php echo htmlspecialchars($parcel['parcel_type']); ?></span>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <small class="text-muted d-block">Weight</small>
                        <span class="fw-semibold"><?php echo htmlspecialchars($parcel['weight']); ?> kg</span>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <small class="text-muted d-block">Booked On</small>
                        <span class="fw-semibold"><?php echo date('M d, Y - h:i A', strtotime($parcel['created_at'])); ?></span>
                    </div>
                </div>
                
                <!-- Sender Details -->
                <h5 class="fw-bold mb-3 text-indigo"><i class="fa-solid fa-circle-arrow-up me-1"></i> Sender (Collection Location)</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Contact Person</small>
                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($parcel['sender_name']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Phone Number</small>
                        <a href="tel:<?php echo htmlspecialchars($parcel['sender_phone']); ?>" class="fw-semibold text-indigo">
                            <i class="fa-solid fa-phone me-1 small"></i><?php echo htmlspecialchars($parcel['sender_phone']); ?>
                        </a>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Pickup Address</small>
                        <div class="p-2 border rounded bg-white mt-1" style="white-space: pre-line;"><?php echo htmlspecialchars($parcel['pickup_address']); ?></div>
                    </div>
                </div>
                
                <hr class="my-4 text-muted">
                
                <!-- Receiver Details -->
                <h5 class="fw-bold mb-3 text-success"><i class="fa-solid fa-circle-arrow-down me-1"></i> Recipient (Destination)</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Contact Person</small>
                        <span class="fw-semibold text-dark"><?php echo htmlspecialchars($parcel['receiver_name']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Phone Number</small>
                        <a href="tel:<?php echo htmlspecialchars($parcel['receiver_phone']); ?>" class="fw-semibold text-success">
                            <i class="fa-solid fa-phone me-1 small"></i><?php echo htmlspecialchars($parcel['receiver_phone']); ?>
                        </a>
                    </div>
                    <div class="col-12">
                        <small class="text-muted d-block">Delivery Address</small>
                        <div class="p-2 border rounded bg-white mt-1" style="white-space: pre-line;"><?php echo htmlspecialchars($parcel['delivery_address']); ?></div>
                    </div>
                </div>
                
                <hr class="my-4 text-muted">
                
                <!-- Action Row -->
                <div class="text-center mt-4">
                    <?php if ($parcel['status'] !== 'Delivered'): ?>
                        <h5 class="fw-bold mb-3 text-muted">Action Required</h5>
                        <form action="dashboard.php" method="POST" class="d-inline-block w-100">
                            <input type="hidden" name="parcel_id" value="<?php echo $parcel['id']; ?>">
                            <?php if ($parcel['status'] === 'Assigned'): ?>
                                <input type="hidden" name="action" value="Picked Up">
                                <button type="submit" class="btn btn-primary btn-lg w-100 py-3">
                                    <i class="fa-solid fa-box-open me-2"></i> Confirm Pickup (Mark Picked Up)
                                </button>
                            <?php elseif ($parcel['status'] === 'Picked Up'): ?>
                                <input type="hidden" name="action" value="In Transit">
                                <button type="submit" class="btn btn-warning btn-lg w-100 py-3 text-white">
                                    <i class="fa-solid fa-truck-fast me-2"></i> Start Transit (Mark In Transit)
                                </button>
                            <?php elseif ($parcel['status'] === 'In Transit'): ?>
                                <input type="hidden" name="action" value="Delivered">
                                <button type="submit" class="btn btn-success btn-lg w-100 py-3">
                                    <i class="fa-solid fa-circle-check me-2"></i> Confirm Delivery (Mark Delivered)
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success py-3 mb-0" role="alert">
                            <i class="fa-solid fa-circle-check me-2 fs-4"></i>
                            <span class="fs-5 fw-bold">Delivery Completed Successfully!</span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>
</div>

<?php 
require_once $path_prefix . 'includes/footer.php'; 
?>
