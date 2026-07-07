<?php
/**
 * Customer Dashboard (My Parcels)
 * Courier & Parcel Tracking System
 */

$page_title = "My Bookings - SwiftPost";
$path_prefix = "../";
require_once $path_prefix . 'includes/header.php';

// Protect page
guard_customer();

$customer_id = $_SESSION['user_id'];

// Query customer parcels
$query = "SELECT p.*, r.name as rider_name 
          FROM parcels p 
          LEFT JOIN riders r ON p.rider_id = r.id 
          WHERE p.customer_id = ? 
          ORDER BY p.created_at DESC";

$parcels = [];
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $parcels[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="row mb-4 align-items-center">
    <div class="col-sm-6">
        <h2 class="fw-bold mb-0">My Parcels</h2>
        <p class="text-muted mb-0">Manage and track your booked shipments.</p>
    </div>
    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
        <a href="book.php" class="btn btn-primary">
            <i class="fa-solid fa-plus me-1"></i> Book New Parcel
        </a>
    </div>
</div>

<div class="card glass-card border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Tracking ID</th>
                        <th>Recipient</th>
                        <th>Delivery Address</th>
                        <th>Type / Weight</th>
                        <th>Status</th>
                        <th>Booked On</th>
                        <th class="pe-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parcels)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No parcels booked yet</h5>
                                <p class="text-muted small">Once you book a parcel, it will appear here.</p>
                                <a href="book.php" class="btn btn-primary btn-sm mt-2">Book Your First Parcel</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($parcels as $p): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-indigo"><?php echo htmlspecialchars($p['tracking_id']); ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($p['receiver_name']); ?></div>
                                    <small class="text-muted"><i class="fa-solid fa-phone me-1 small"></i><?php echo htmlspecialchars($p['receiver_phone']); ?></small>
                                </td>
                                <td>
                                    <span class="d-inline-block text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($p['delivery_address']); ?>">
                                        <?php echo htmlspecialchars($p['delivery_address']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($p['parcel_type']); ?></span>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($p['weight']); ?> kg</small>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo get_badge_class($p['status']); ?>">
                                        <?php echo htmlspecialchars($p['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></small>
                                </td>
                                <td class="pe-4 text-center">
                                    <a href="<?php echo $path_prefix; ?>index.php?tracking_id=<?php echo urlencode($p['tracking_id']); ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i> Track
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
require_once $path_prefix . 'includes/footer.php'; 
?>
