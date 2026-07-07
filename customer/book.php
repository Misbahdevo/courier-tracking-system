<?php
/**
 * Book New Parcel Form
 * Courier & Parcel Tracking System
 */

$page_title = "Book a Parcel - SwiftPost";
$path_prefix = "../";
require_once $path_prefix . 'includes/header.php';

// Guard customer access
guard_customer();

$customer_id = $_SESSION['user_id'];

// Get user profile details to prefill sender information
$sender_name = $_SESSION['user_name'];
$sender_phone = '';

$user_q = mysqli_query($conn, "SELECT phone FROM users WHERE id = $customer_id LIMIT 1");
if ($user_q && $row = mysqli_fetch_assoc($user_q)) {
    $sender_phone = $row['phone'];
}

$pickup_address = '';
$receiver_name = '';
$receiver_phone = '';
$delivery_address = '';
$weight = '';
$parcel_type = 'Package';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_name = sanitize($conn, $_POST['sender_name']);
    $sender_phone = sanitize($conn, $_POST['sender_phone']);
    $pickup_address = sanitize($conn, $_POST['pickup_address']);
    $receiver_name = sanitize($conn, $_POST['receiver_name']);
    $receiver_phone = sanitize($conn, $_POST['receiver_phone']);
    $delivery_address = sanitize($conn, $_POST['delivery_address']);
    $weight = floatval($_POST['weight']);
    $parcel_type = sanitize($conn, $_POST['parcel_type']);
    
    // Server-side validation
    if (empty($sender_name)) $errors[] = "Sender Name is required.";
    if (empty($sender_phone)) $errors[] = "Sender Phone is required.";
    if (empty($pickup_address)) $errors[] = "Pickup Address is required.";
    if (empty($receiver_name)) $errors[] = "Receiver Name is required.";
    if (empty($receiver_phone)) $errors[] = "Receiver Phone is required.";
    if (empty($delivery_address)) $errors[] = "Delivery Address is required.";
    if ($weight <= 0) $errors[] = "Parcel weight must be greater than 0.";
    if (!in_array($parcel_type, ['Document', 'Package', 'Fragile'])) $errors[] = "Invalid parcel type.";
    
    if (empty($errors)) {
        // Start Transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Generate unique Tracking ID
            $tracking_id = generate_tracking_id($conn);
            
            // Insert parcel
            $insert_parcel = "INSERT INTO parcels (tracking_id, customer_id, sender_name, sender_phone, pickup_address, receiver_name, receiver_phone, delivery_address, weight, parcel_type, status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
            
            $stmt = mysqli_prepare($conn, $insert_parcel);
            mysqli_stmt_bind_param($stmt, "sissssssds", $tracking_id, $customer_id, $sender_name, $sender_phone, $pickup_address, $receiver_name, $receiver_phone, $delivery_address, $weight, $parcel_type);
            mysqli_stmt_execute($stmt);
            
            $parcel_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // Insert initial status history
            $insert_history = "INSERT INTO parcel_status_history (parcel_id, status) VALUES (?, 'Pending')";
            $hist_stmt = mysqli_prepare($conn, $insert_history);
            mysqli_stmt_bind_param($hist_stmt, "i", $parcel_id);
            mysqli_stmt_execute($hist_stmt);
            mysqli_stmt_close($hist_stmt);
            
            // Commit Transaction
            mysqli_commit($conn);
            
            set_flash_message('success', "Parcel booked successfully! Tracking ID: $tracking_id");
            header("Location: dashboard.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback on failure
            mysqli_rollback($conn);
            $errors[] = "Failed to book parcel. Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <div class="card glass-card border-0 mb-4">
            <div class="card-body p-4 p-md-5">
                <h2 class="fw-bold mb-4 text-center">Book a New Parcel</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="book.php" method="POST" class="needs-validation" novalidate>
                    
                    <!-- Sender Details Row -->
                    <h5 class="fw-bold mb-3 text-indigo"><i class="fa-solid fa-circle-arrow-up me-1"></i> Sender Details</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="sender_name" class="form-label fw-semibold small text-muted text-uppercase">Sender Name</label>
                            <input type="text" class="form-control" id="sender_name" name="sender_name" value="<?php echo htmlspecialchars($sender_name); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="sender_phone" class="form-label fw-semibold small text-muted text-uppercase">Sender Phone</label>
                            <input type="text" class="form-control" id="sender_phone" name="sender_phone" value="<?php echo htmlspecialchars($sender_phone); ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="pickup_address" class="form-label fw-semibold small text-muted text-uppercase">Pickup Address</label>
                            <textarea class="form-control" id="pickup_address" name="pickup_address" rows="3" placeholder="Enter full address for parcel collection" required><?php echo htmlspecialchars($pickup_address); ?></textarea>
                        </div>
                    </div>
                    
                    <hr class="my-4 text-muted">
                    
                    <!-- Receiver Details Row -->
                    <h5 class="fw-bold mb-3 text-success"><i class="fa-solid fa-circle-arrow-down me-1"></i> Receiver Details</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="receiver_name" class="form-label fw-semibold small text-muted text-uppercase">Receiver Name</label>
                            <input type="text" class="form-control" id="receiver_name" name="receiver_name" value="<?php echo htmlspecialchars($receiver_name); ?>" placeholder="Recipient's Name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="receiver_phone" class="form-label fw-semibold small text-muted text-uppercase">Receiver Phone</label>
                            <input type="text" class="form-control" id="receiver_phone" name="receiver_phone" value="<?php echo htmlspecialchars($receiver_phone); ?>" placeholder="Recipient's Contact Number" required>
                        </div>
                        <div class="col-12">
                            <label for="delivery_address" class="form-label fw-semibold small text-muted text-uppercase">Delivery Address</label>
                            <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" placeholder="Enter full destination address" required><?php echo htmlspecialchars($delivery_address); ?></textarea>
                        </div>
                    </div>
                    
                    <hr class="my-4 text-muted">
                    
                    <!-- Parcel Specifications Row -->
                    <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-scale-balanced me-1"></i> Parcel Specifications</h5>
                    <div class="row g-3 mb-5">
                        <div class="col-md-6">
                            <label for="weight" class="form-label fw-semibold small text-muted text-uppercase">Weight (in kg)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="weight" name="weight" step="0.01" min="0.01" value="<?php echo htmlspecialchars($weight); ?>" placeholder="e.g. 1.50" required>
                                <span class="input-group-text bg-light text-muted">kg</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="parcel_type" class="form-label fw-semibold small text-muted text-uppercase">Parcel Type</label>
                            <select class="form-select" id="parcel_type" name="parcel_type" required>
                                <option value="Package" <?php echo ($parcel_type === 'Package') ? 'selected' : ''; ?>>Package</option>
                                <option value="Document" <?php echo ($parcel_type === 'Document') ? 'selected' : ''; ?>>Document</option>
                                <option value="Fragile" <?php echo ($parcel_type === 'Fragile') ? 'selected' : ''; ?>>Fragile / Handle with Care</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-3 fs-5 shadow-sm">
                        <i class="fa-solid fa-truck-fast me-2"></i> Book Shipment
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
// Client-side Bootstrap validation
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
})()
</script>

<?php 
require_once $path_prefix . 'includes/footer.php'; 
?>
