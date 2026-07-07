<?php
/**
 * Admin Dashboard
 * Courier & Parcel Tracking System
 */

$page_title = "Admin Dashboard - SwiftPost";
$path_prefix = "../";
require_once $path_prefix . 'includes/header.php';

// Guard admin access
guard_admin();

$active_tab = 'dashboard';

// Fetch statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'assigned' => 0,
    'in_transit' => 0,
    'delivered' => 0
];

// Total
$q = mysqli_query($conn, "SELECT COUNT(*) as count FROM parcels");
if ($q && $row = mysqli_fetch_assoc($q)) $stats['total'] = $row['count'];

// Pending
$q = mysqli_query($conn, "SELECT COUNT(*) as count FROM parcels WHERE status = 'Pending'");
if ($q && $row = mysqli_fetch_assoc($q)) $stats['pending'] = $row['count'];

// Assigned
$q = mysqli_query($conn, "SELECT COUNT(*) as count FROM parcels WHERE status = 'Assigned'");
if ($q && $row = mysqli_fetch_assoc($q)) $stats['assigned'] = $row['count'];

// In Transit
$q = mysqli_query($conn, "SELECT COUNT(*) as count FROM parcels WHERE status = 'In Transit'");
if ($q && $row = mysqli_fetch_assoc($q)) $stats['in_transit'] = $row['count'];

// Delivered
$q = mysqli_query($conn, "SELECT COUNT(*) as count FROM parcels WHERE status = 'Delivered'");
if ($q && $row = mysqli_fetch_assoc($q)) $stats['delivered'] = $row['count'];

// Fetch recent parcels (last 5)
$recent_parcels = [];
$q_recent = mysqli_query($conn, "SELECT p.*, u.name as customer_name 
                                 FROM parcels p 
                                 JOIN users u ON p.customer_id = u.id 
                                 ORDER BY p.created_at DESC LIMIT 5");
if ($q_recent) {
    while ($row = mysqli_fetch_assoc($q_recent)) {
        $recent_parcels[] = $row;
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
        <h2 class="fw-bold mb-4">Dashboard Overview</h2>
        
        <!-- Summary Stats Row -->
        <div class="row g-3 mb-5">
            <!-- Total -->
            <div class="col-sm-6 col-lg-4">
                <div class="card stat-card bg-stat-total">
                    <div class="card-body">
                        <small class="text-white-50 text-uppercase fw-bold">Total Shipments</small>
                        <h2 class="display-5 fw-bold mb-0"><?php echo $stats['total']; ?></h2>
                        <i class="fa-solid fa-cubes stat-card-icon"></i>
                    </div>
                </div>
            </div>
            
            <!-- Pending -->
            <div class="col-sm-6 col-lg-4">
                <div class="card stat-card bg-stat-pending">
                    <div class="card-body">
                        <small class="text-white-50 text-uppercase fw-bold">Pending Booking</small>
                        <h2 class="display-5 fw-bold mb-0"><?php echo $stats['pending']; ?></h2>
                        <i class="fa-solid fa-clock-rotate-left stat-card-icon"></i>
                    </div>
                </div>
            </div>
            
            <!-- Assigned -->
            <div class="col-sm-6 col-lg-4">
                <div class="card stat-card bg-stat-assigned">
                    <div class="card-body">
                        <small class="text-white-50 text-uppercase fw-bold">Rider Assigned</small>
                        <h2 class="display-5 fw-bold mb-0"><?php echo $stats['assigned']; ?></h2>
                        <i class="fa-solid fa-user-tag stat-card-icon"></i>
                    </div>
                </div>
            </div>
            
            <!-- In Transit -->
            <div class="col-sm-6 col-lg-4 offset-lg-2">
                <div class="card stat-card bg-stat-intransit">
                    <div class="card-body">
                        <small class="text-white-50 text-uppercase fw-bold">In Transit</small>
                        <h2 class="display-5 fw-bold mb-0"><?php echo $stats['in_transit']; ?></h2>
                        <i class="fa-solid fa-truck-fast stat-card-icon"></i>
                    </div>
                </div>
            </div>
            
            <!-- Delivered -->
            <div class="col-sm-6 col-lg-4">
                <div class="card stat-card bg-stat-delivered">
                    <div class="card-body">
                        <small class="text-white-50 text-uppercase fw-bold">Delivered</small>
                        <h2 class="display-5 fw-bold mb-0"><?php echo $stats['delivered']; ?></h2>
                        <i class="fa-solid fa-circle-check stat-card-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities Card -->
        <div class="card glass-card border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0">Recent Shipments</h4>
                <a href="parcels.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Tracking ID</th>
                                <th>Customer</th>
                                <th>Receiver</th>
                                <th>Status</th>
                                <th class="pe-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_parcels)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No shipments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_parcels as $p): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-indigo"><?php echo htmlspecialchars($p['tracking_id']); ?></td>
                                        <td><?php echo htmlspecialchars($p['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($p['receiver_name']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo get_badge_class($p['status']); ?>">
                                                <?php echo htmlspecialchars($p['status']); ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-muted small"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
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
