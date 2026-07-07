<?php
/**
 * Public Landing Page & Parcel Tracking Portal
 * Courier & Parcel Tracking System
 */

$page_title = "Track Your Parcel - SwiftPost";
$path_prefix = "./";
require_once $path_prefix . 'includes/header.php';

$tracking_id = '';
$parcel = null;
$history = [];
$error_msg = '';

if (isset($_GET['tracking_id'])) {
    $tracking_id = sanitize($conn, $_GET['tracking_id']);
    
    if (empty($tracking_id)) {
        $error_msg = "Please enter a tracking ID.";
    } else {
        // Query parcel details
        $query = "SELECT p.*, r.name as rider_name, r.phone as rider_phone 
                  FROM parcels p 
                  LEFT JOIN riders r ON p.rider_id = r.id 
                  WHERE p.tracking_id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $tracking_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                $parcel = $row;
                
                // Query status history
                $hist_query = "SELECT * FROM parcel_status_history WHERE parcel_id = ? ORDER BY updated_at ASC";
                $hist_stmt = mysqli_prepare($conn, $hist_query);
                if ($hist_stmt) {
                    mysqli_stmt_bind_param($hist_stmt, "i", $parcel['id']);
                    mysqli_stmt_execute($hist_stmt);
                    $hist_res = mysqli_stmt_get_result($hist_stmt);
                    
                    while ($h_row = mysqli_fetch_assoc($hist_res)) {
                        // Store update time indexed by status name for easy stepper mapping
                        $history[$h_row['status']] = $h_row['updated_at'];
                    }
                    mysqli_stmt_close($hist_stmt);
                }
            } else {
                $error_msg = "No parcel found with Tracking ID: " . htmlspecialchars($tracking_id);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        
        <!-- Hero Section -->
        <div class="text-center my-5">
            <h1 class="display-4 fw-bold text-dark mb-3">Swift & Secure Delivery</h1>
            <p class="lead text-muted">Track your courier or parcel status in real-time with zero hassle.</p>
        </div>

        <!-- Tracking Search Card -->
        <div class="card glass-card border-0 mb-5">
            <div class="card-body p-4 p-md-5">
                <form action="index.php" method="GET">
                    <label for="tracking_id" class="form-label fw-semibold text-muted text-uppercase mb-2 small">Enter Tracking ID</label>
                    <div class="input-group input-group-lg shadow-sm rounded">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="fa-solid fa-receipt"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 border-end-0" id="tracking_id" name="tracking_id" placeholder="e.g. TRK12345678" value="<?php echo htmlspecialchars($tracking_id); ?>" required>
                        <button class="btn btn-primary px-4" type="submit">
                            <i class="fa-solid fa-magnifying-glass me-2"></i> Track Now
                        </button>
                    </div>
                </form>
                
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger mt-4 mb-0 d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>
                        <div><?php echo $error_msg; ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tracking Results Display -->
        <?php if ($parcel): ?>
            <div class="card glass-card border-0 mb-5">
                <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 text-muted text-uppercase small fw-bold">Parcel Details</h5>
                        <h3 class="mb-0 fw-bold text-indigo"><?php echo htmlspecialchars($parcel['tracking_id']); ?></h3>
                    </div>
                    <div>
                        <span class="status-badge <?php echo get_badge_class($parcel['status']); ?> fs-6 py-2 px-3">
                            <i class="fa-solid fa-circle-dot me-1"></i> <?php echo htmlspecialchars($parcel['status']); ?>
                        </span>
                    </div>
                </div>
                
                <hr class="mx-4 my-2 text-muted">
                
                <div class="card-body p-4">
                    <!-- Parcel Specs Grid -->
                    <div class="row g-4 mb-4">
                        <div class="col-sm-6 col-md-3">
                            <small class="text-muted text-uppercase fw-semibold d-block">Sender Name</small>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($parcel['sender_name']); ?></span>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <small class="text-muted text-uppercase fw-semibold d-block">Receiver Name</small>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($parcel['receiver_name']); ?></span>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <small class="text-muted text-uppercase fw-semibold d-block">Weight</small>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($parcel['weight']); ?> kg</span>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <small class="text-muted text-uppercase fw-semibold d-block">Parcel Type</small>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($parcel['parcel_type']); ?></span>
                        </div>
                    </div>
                    
                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <small class="text-muted text-uppercase fw-semibold d-block mb-1"><i class="fa-solid fa-location-dot text-indigo me-1"></i> Pickup Address</small>
                                <span class="text-dark" style="white-space: pre-line;"><?php echo htmlspecialchars($parcel['pickup_address']); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <small class="text-muted text-uppercase fw-semibold d-block mb-1"><i class="fa-solid fa-house-chimney text-success me-1"></i> Delivery Address</small>
                                <span class="text-dark" style="white-space: pre-line;"><?php echo htmlspecialchars($parcel['delivery_address']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Stepper Timeline -->
                    <h5 class="fw-bold mb-4 text-center">Shipment Journey</h5>
                    
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="tracking-stepper">
                                
                                <?php
                                // Ordered list of all status milestones
                                $statuses = ['Pending', 'Assigned', 'Picked Up', 'In Transit', 'Delivered'];
                                
                                // Determine the current stage index
                                $current_stage_idx = array_search($parcel['status'], $statuses);
                                
                                foreach ($statuses as $idx => $status_name):
                                    $is_completed = isset($history[$status_name]);
                                    $is_active = ($parcel['status'] === $status_name);
                                    
                                    $class = '';
                                    if ($is_completed) {
                                        $class = 'completed';
                                    }
                                    if ($is_active) {
                                        $class = 'active';
                                    }
                                    
                                    // Icons for each step
                                    $icons = [
                                        'Pending' => '<i class="fa-solid fa-receipt text-white small"></i>',
                                        'Assigned' => '<i class="fa-solid fa-user-check text-white small"></i>',
                                        'Picked Up' => '<i class="fa-solid fa-box-open text-white small"></i>',
                                        'In Transit' => '<i class="fa-solid fa-truck-ramp-box text-white small"></i>',
                                        'Delivered' => '<i class="fa-solid fa-circle-check text-white small"></i>'
                                    ];
                                ?>
                                    <div class="step-item <?php echo $class; ?>">
                                        <div class="step-badge">
                                            <?php 
                                            if ($is_completed && !$is_active) {
                                                echo '<i class="fa-solid fa-check text-white small"></i>';
                                            } else {
                                                echo $icons[$status_name] ?? '';
                                            }
                                            ?>
                                        </div>
                                        <div class="step-content ms-2">
                                            <h6 class="step-title mb-0"><?php echo $status_name; ?></h6>
                                            <?php if ($is_completed): ?>
                                                <span class="step-date">
                                                    <i class="fa-regular fa-clock me-1"></i> 
                                                    <?php echo date('F d, Y - h:i A', strtotime($history[$status_name])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="step-date text-muted font-italic">Not reached yet</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php 
require_once $path_prefix . 'includes/footer.php'; 
?>
