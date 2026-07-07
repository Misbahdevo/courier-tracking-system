<?php
/**
 * Admin Manage Customers
 * Courier & Parcel Tracking System
 */

$page_title = "Manage Customers - SwiftPost";
$path_prefix = "../";
require_once $path_prefix . 'includes/header.php';

// Guard admin access
guard_admin();

$active_tab = 'customers';

// Handle Block/Unblock toggle
if (isset($_GET['action']) && isset($_GET['id'])) {
    $cust_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($cust_id > 0) {
        $status = ($action === 'block') ? 'blocked' : 'active';
        $update_q = "UPDATE users SET status = ? WHERE id = ? AND role = 'customer'";
        $stmt = mysqli_prepare($conn, $update_q);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $status, $cust_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            set_flash_message('success', "Customer status updated to '$status' successfully.");
        }
    }
    header("Location: customers.php");
    exit();
}

// Fetch customers list with aggregate parcel booking counts
$query = "SELECT u.id, u.name, u.email, u.phone, u.status, u.created_at, COUNT(p.id) as booking_count 
          FROM users u 
          LEFT JOIN parcels p ON u.id = p.customer_id 
          WHERE u.role = 'customer' 
          GROUP BY u.id 
          ORDER BY u.created_at DESC";

$customers = [];
$q_customers = mysqli_query($conn, $query);
if ($q_customers) {
    while ($row = mysqli_fetch_assoc($q_customers)) {
        $customers[] = $row;
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
            <h2 class="fw-bold mb-0">Manage Customers</h2>
            <p class="text-muted mb-0">Monitor registered customers, their booking history, and access status.</p>
        </div>
        
        <!-- Customers Card -->
        <div class="card glass-card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Customer Details</th>
                                <th>Phone</th>
                                <th>Registered On</th>
                                <th class="text-center">Total Bookings</th>
                                <th>Status</th>
                                <th class="pe-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-users-slash fa-3x mb-3"></i>
                                        <h5>No registered customers found</h5>
                                        <p class="small">Customer records will appear once they sign up.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $c): ?>
                                    <tr>
                                        <!-- Customer Details -->
                                        <td class="ps-4">
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($c['name']); ?></div>
                                            <small class="text-muted"><i class="fa-regular fa-envelope me-1 small"></i><?php echo htmlspecialchars($c['email']); ?></small>
                                        </td>
                                        
                                        <!-- Phone -->
                                        <td>
                                            <span><?php echo htmlspecialchars($c['phone']); ?></span>
                                        </td>
                                        
                                        <!-- Registration Date -->
                                        <td class="text-muted small">
                                            <?php echo date('M d, Y', strtotime($c['created_at'])); ?>
                                        </td>
                                        
                                        <!-- Booking Count -->
                                        <td class="text-center fw-bold text-indigo">
                                            <?php echo $c['booking_count']; ?>
                                        </td>
                                        
                                        <!-- Status Badge -->
                                        <td>
                                            <?php if ($c['status'] === 'active'): ?>
                                                <span class="status-badge badge-delivered">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge badge-pending">Blocked</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Action Block Toggle -->
                                        <td class="pe-4 text-center">
                                            <?php if ($c['status'] === 'active'): ?>
                                                <a href="customers.php?action=block&id=<?php echo $c['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to block this customer? They will be signed out and unable to log in.')">
                                                    <i class="fa-solid fa-user-slash me-1"></i> Block
                                                </a>
                                            <?php else: ?>
                                                <a href="customers.php?action=unblock&id=<?php echo $c['id']; ?>" class="btn btn-outline-success btn-sm">
                                                    <i class="fa-solid fa-user-check me-1"></i> Unblock
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
