<?php
/**
 * Admin Reports
 * Courier & Parcel Tracking System
 */

$page_title = "System Reports - SwiftPost";
$path_prefix = "../";
require_once $path_prefix . 'includes/header.php';

// Guard admin access
guard_admin();

$active_tab = 'reports';

// Default Filter values
$status_filter = isset($_GET['status']) ? sanitize($conn, $_GET['status']) : 'All';
$date_from = isset($_GET['date_from']) ? sanitize($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($conn, $_GET['date_to']) : '';

// Build Query dynamically
$where_clauses = [];

if ($status_filter !== 'All' && in_array($status_filter, ['Pending', 'Assigned', 'Picked Up', 'In Transit', 'Delivered'])) {
    $where_clauses[] = "p.status = '$status_filter'";
}

if (!empty($date_from)) {
    $where_clauses[] = "DATE(p.created_at) >= '$date_from'";
}

if (!empty($date_to)) {
    $where_clauses[] = "DATE(p.created_at) <= '$date_to'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Fetch filtered reports
$query = "SELECT p.*, u.name as customer_name, r.name as rider_name 
          FROM parcels p 
          JOIN users u ON p.customer_id = u.id 
          LEFT JOIN riders r ON p.rider_id = r.id 
          $where_sql 
          ORDER BY p.created_at DESC";

$report_data = [];
$q_reports = mysqli_query($conn, $query);

$total_parcels = 0;
$total_weight = 0.0;

if ($q_reports) {
    while ($row = mysqli_fetch_assoc($q_reports)) {
        $report_data[] = $row;
        $total_parcels++;
        $total_weight += floatval($row['weight']);
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
        <div class="mb-4">
            <h2 class="fw-bold mb-0">System Reports</h2>
            <p class="text-muted mb-0">Generate, filter, and inspect detailed shipment records.</p>
        </div>
        
        <!-- Filter Card -->
        <div class="card glass-card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-filter me-1 text-indigo"></i> Filter Records</h5>
                <form action="reports.php" method="GET" class="row g-3 align-items-end">
                    
                    <!-- Status Filter -->
                    <div class="col-md-4">
                        <label for="status" class="form-label small fw-semibold text-muted text-uppercase">Status</label>
                        <select class="form-select form-select-sm" id="status" name="status">
                            <option value="All" <?php echo ($status_filter === 'All') ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="Pending" <?php echo ($status_filter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Assigned" <?php echo ($status_filter === 'Assigned') ? 'selected' : ''; ?>>Assigned</option>
                            <option value="Picked Up" <?php echo ($status_filter === 'Picked Up') ? 'selected' : ''; ?>>Picked Up</option>
                            <option value="In Transit" <?php echo ($status_filter === 'In Transit') ? 'selected' : ''; ?>>In Transit</option>
                            <option value="Delivered" <?php echo ($status_filter === 'Delivered') ? 'selected' : ''; ?>>Delivered</option>
                        </select>
                    </div>
                    
                    <!-- Date From -->
                    <div class="col-md-3 col-sm-6">
                        <label for="date_from" class="form-label small fw-semibold text-muted text-uppercase">Date From</label>
                        <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <!-- Date To -->
                    <div class="col-md-3 col-sm-6">
                        <label for="date_to" class="form-label small fw-semibold text-muted text-uppercase">Date To</label>
                        <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    
                    <!-- Action Button -->
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-arrows-rotate me-1"></i> Filter
                        </button>
                    </div>
                    
                </form>
            </div>
        </div>
        
        <!-- Summary Badges -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="p-3 bg-white border rounded shadow-sm d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Filtered Parcels</small>
                        <span class="fs-4 fw-bold text-indigo"><?php echo $total_parcels; ?></span>
                    </div>
                    <i class="fa-solid fa-boxes-stacked fa-2x text-muted opacity-50"></i>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="p-3 bg-white border rounded shadow-sm d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block text-uppercase fw-semibold">Total Net Weight</small>
                        <span class="fs-4 fw-bold text-success"><?php echo number_format($total_weight, 2); ?> kg</span>
                    </div>
                    <i class="fa-solid fa-weight-hanging fa-2x text-muted opacity-50"></i>
                </div>
            </div>
        </div>

        <!-- Report Table Card -->
        <div class="card glass-card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Tracking ID</th>
                                <th>Sender</th>
                                <th>Receiver Address</th>
                                <th>Rider</th>
                                <th>Weight/Type</th>
                                <th>Status</th>
                                <th class="pe-4">Booking Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report_data)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-rectangle-xmark fa-3x mb-3 text-muted"></i>
                                        <h5>No records found matching filters</h5>
                                        <p class="small">Try adjusting status or expanding the date range query.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($report_data as $r): ?>
                                    <tr>
                                        <!-- Tracking ID -->
                                        <td class="ps-4 fw-bold text-indigo">
                                            <?php echo htmlspecialchars($r['tracking_id']); ?>
                                        </td>
                                        
                                        <!-- Sender -->
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($r['sender_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($r['sender_phone']); ?></small>
                                        </td>
                                        
                                        <!-- Receiver Address -->
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($r['receiver_name']); ?></div>
                                            <span class="small d-inline-block text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($r['delivery_address']); ?>">
                                                <?php echo htmlspecialchars($r['delivery_address']); ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Rider -->
                                        <td>
                                            <?php if ($r['rider_id']): ?>
                                                <span class="fw-semibold text-dark"><i class="fa-solid fa-motorcycle me-1 text-muted small"></i><?php echo htmlspecialchars($r['rider_name']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small italic">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Specs -->
                                        <td>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r['parcel_type']); ?></span>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($r['weight']); ?> kg</small>
                                        </td>
                                        
                                        <!-- Status -->
                                        <td>
                                            <span class="status-badge <?php echo get_badge_class($r['status']); ?>">
                                                <?php echo htmlspecialchars($r['status']); ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Date -->
                                        <td class="pe-4 text-muted small">
                                            <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
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
