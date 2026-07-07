<?php
/**
 * Admin Manage Parcels
 * Courier & Parcel Tracking System
 */

$page_title = "Manage Parcels - SwiftPost";
$path_prefix = "../";
require_once $path_prefix . 'includes/header.php';

// Guard admin access
guard_admin();

$active_tab = 'parcels';

// Fetch active riders list (to populate assignment dropdowns)
$riders = [];
$q_riders = mysqli_query($conn, "SELECT id, name, area FROM riders WHERE status = 'active' ORDER BY name ASC");
if ($q_riders) {
    while ($row = mysqli_fetch_assoc($q_riders)) {
        $riders[] = $row;
    }
}

// Handle Rider Assignment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_rider'])) {
    $parcel_id = intval($_POST['parcel_id']);
    $rider_id = intval($_POST['rider_id']);
    
    if ($parcel_id <= 0 || $rider_id <= 0) {
        set_flash_message('danger', "Invalid parcel or rider selection.");
    } else {
        // Begin Transaction
        mysqli_begin_transaction($conn);
        try {
            // Update parcel
            $update_q = "UPDATE parcels SET rider_id = ?, status = 'Assigned' WHERE id = ? AND status = 'Pending'";
            $update_stmt = mysqli_prepare($conn, $update_q);
            mysqli_stmt_bind_param($update_stmt, "ii", $rider_id, $parcel_id);
            mysqli_stmt_execute($update_stmt);
            $affected_rows = mysqli_stmt_affected_rows($update_stmt);
            mysqli_stmt_close($update_stmt);
            
            if ($affected_rows > 0) {
                // Insert status history
                $history_q = "INSERT INTO parcel_status_history (parcel_id, status) VALUES (?, 'Assigned')";
                $history_stmt = mysqli_prepare($conn, $history_q);
                mysqli_stmt_bind_param($history_stmt, "i", $parcel_id);
                mysqli_stmt_execute($history_stmt);
                mysqli_stmt_close($history_stmt);
                
                mysqli_commit($conn);
                set_flash_message('success', "Rider assigned successfully. Parcel status set to 'Assigned'.");
            } else {
                mysqli_rollback($conn);
                set_flash_message('danger', "Failed to assign rider. Either rider is invalid or parcel is already assigned.");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            set_flash_message('danger', "Database error occurred during assignment.");
        }
    }
    
    header("Location: parcels.php" . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit();
}

// Determine status filter
$status_filter = isset($_GET['status']) ? sanitize($conn, $_GET['status']) : 'All';
$where_clause = "";
if ($status_filter !== 'All' && in_array($status_filter, ['Pending', 'Assigned', 'Picked Up', 'In Transit', 'Delivered'])) {
    $where_clause = "WHERE p.status = '$status_filter'";
}

// Fetch all parcels
$parcels_query = "SELECT p.*, u.name as customer_name, r.name as rider_name, r.area as rider_area 
                  FROM parcels p 
                  JOIN users u ON p.customer_id = u.id 
                  LEFT JOIN riders r ON p.rider_id = r.id 
                  $where_clause
                  ORDER BY p.created_at DESC";

$parcels = [];
$q_parcels = mysqli_query($conn, $parcels_query);
if ($q_parcels) {
    while ($row = mysqli_fetch_assoc($q_parcels)) {
        $parcels[] = $row;
    }
}
?>

<div class="row">
    <!-- Admin Sidebar -->
    <div class="col-lg-3 col-md-4 mb-4">
        <div class="card glass-card border-0 shadow-sm">
            <div class="list-group list-group-flush rounded-3">
                <a href="dashboard.php" class="list-group-item list-group-item-action py-3 px-4 fw-semibold <?php echo ($active_tab === 'dashboard') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-line me-2"></i> Dashboard
                </a>
                <a href="parcels.php" class="list-group-item list-group-item-action py-3 px-4 fw-semibold <?php echo ($active_tab === 'parcels') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-boxes-packing me-2"></i> Manage Parcels
                </a>
                <a href="riders.php" class="list-group-item list-group-item-action py-3 px-4 fw-semibold <?php echo ($active_tab === 'riders') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-motorcycle me-2"></i> Manage Riders
                </a>
                <a href="customers.php" class="list-group-item list-group-item-action py-3 px-4 fw-semibold <?php echo ($active_tab === 'customers') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users me-2"></i> Manage Customers
                </a>
                <a href="reports.php" class="list-group-item list-group-item-action py-3 px-4 fw-semibold <?php echo ($active_tab === 'reports') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-invoice me-2"></i> Reports
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="col-lg-9 col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 class="fw-bold mb-0">Manage Parcels</h2>
                <p class="text-muted mb-0">View all customer bookings and assign delivery riders.</p>
            </div>
        </div>
        
        <!-- Status Filter Pills -->
        <div class="mb-4">
            <div class="btn-group flex-wrap shadow-sm rounded">
                <a href="parcels.php?status=All" class="btn btn-sm btn-outline-primary <?php echo ($status_filter === 'All') ? 'active' : ''; ?>">All</a>
                <a href="parcels.php?status=Pending" class="btn btn-sm btn-outline-primary <?php echo ($status_filter === 'Pending') ? 'active' : ''; ?>">Pending</a>
                <a href="parcels.php?status=Assigned" class="btn btn-sm btn-outline-primary <?php echo ($status_filter === 'Assigned') ? 'active' : ''; ?>">Assigned</a>
                <a href="parcels.php?status=Picked Up" class="btn btn-sm btn-outline-primary <?php echo ($status_filter === 'Picked Up') ? 'active' : ''; ?>">Picked Up</a>
                <a href="parcels.php?status=In Transit" class="btn btn-sm btn-outline-primary <?php echo ($status_filter === 'In Transit') ? 'active' : ''; ?>">In Transit</a>
                <a href="parcels.php?status=Delivered" class="btn btn-sm btn-outline-primary <?php echo ($status_filter === 'Delivered') ? 'active' : ''; ?>">Delivered</a>
            </div>
        </div>
        
        <!-- Parcels Card -->
        <div class="card glass-card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Tracking ID</th>
                                <th>Sender</th>
                                <th>Recipient</th>
                                <th>Weight/Type</th>
                                <th>Status</th>
                                <th>Delivery Agent</th>
                                <th class="pe-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($parcels)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-folder-open fa-3x mb-3 text-muted"></i>
                                        <h5>No parcels found</h5>
                                        <p class="small">There are currently no parcels matching this status.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($parcels as $p): ?>
                                    <tr>
                                        <!-- Tracking ID -->
                                        <td class="ps-4">
                                            <span class="fw-bold text-indigo d-block"><?php echo htmlspecialchars($p['tracking_id']); ?></span>
                                            <small class="text-muted">Booked: <?php echo date('M d, Y', strtotime($p['created_at'])); ?></small>
                                        </td>
                                        
                                        <!-- Sender Info -->
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($p['sender_name']); ?></div>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($p['sender_phone']); ?></small>
                                            <span class="small d-inline-block text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($p['pickup_address']); ?>">
                                                <?php echo htmlspecialchars($p['pickup_address']); ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Recipient Info -->
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($p['receiver_name']); ?></div>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($p['receiver_phone']); ?></small>
                                            <span class="small d-inline-block text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($p['delivery_address']); ?>">
                                                <?php echo htmlspecialchars($p['delivery_address']); ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Specs -->
                                        <td>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($p['parcel_type']); ?></span>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($p['weight']); ?> kg</small>
                                        </td>
                                        
                                        <!-- Status Badge -->
                                        <td>
                                            <span class="status-badge <?php echo get_badge_class($p['status']); ?>">
                                                <?php echo htmlspecialchars($p['status']); ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Delivery Agent -->
                                        <td>
                                            <?php if ($p['rider_id']): ?>
                                                <div class="fw-semibold text-dark"><i class="fa-solid fa-motorcycle me-1 text-muted small"></i><?php echo htmlspecialchars($p['rider_name']); ?></div>
                                                <small class="text-muted">Area: <?php echo htmlspecialchars($p['rider_area']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted small italic">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Action Field -->
                                        <td class="pe-4 text-center">
                                            <?php if ($p['status'] === 'Pending'): ?>
                                                <!-- Inline Assign Rider Form -->
                                                <form action="parcels.php<?php echo isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''; ?>" method="POST" class="d-flex align-items-center gap-1 m-0 justify-content-center">
                                                    <input type="hidden" name="parcel_id" value="<?php echo $p['id']; ?>">
                                                    <select class="form-select form-select-sm" name="rider_id" style="width: auto; min-width: 120px;" required>
                                                        <option value="" disabled selected>Select Rider</option>
                                                        <?php foreach ($riders as $r): ?>
                                                            <option value="<?php echo $r['id']; ?>">
                                                                <?php echo htmlspecialchars($r['name']) . ' (' . htmlspecialchars($r['area']) . ')'; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" name="assign_rider" class="btn btn-primary btn-sm px-2">
                                                        Assign
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Track Link -->
                                                <a href="<?php echo $path_prefix; ?>index.php?tracking_id=<?php echo urlencode($p['tracking_id']); ?>" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fa-solid fa-route"></i> Trace
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php 
require_once $path_prefix . 'includes/footer.php'; 
?>
