<?php
/**
 * Customer Registration Page
 * Courier & Parcel Tracking System
 */

$page_title = "Customer Registration - SwiftPost";
$path_prefix = "./";
require_once $path_prefix . 'includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: customer/dashboard.php");
    exit();
}

$name = '';
$email = '';
$phone = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($conn, $_POST['name']);
    $email = sanitize($conn, $_POST['email']);
    $phone = sanitize($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Server-side validation
    if (empty($name)) {
        $errors[] = "Full Name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if email already exists in users table
    if (empty($errors)) {
        $check_email = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $check_email);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errors[] = "This email is already registered.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Insert if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (name, email, password, role, phone, status) VALUES (?, ?, ?, 'customer', ?, 'active')";
        $stmt = mysqli_prepare($conn, $insert_query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hashed_password, $phone);
            if (mysqli_stmt_execute($stmt)) {
                set_flash_message('success', 'Registration successful! Please log in below.');
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Database error. Registration failed. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="row justify-content-center my-4">
    <div class="col-md-8 col-lg-5">
        <div class="card glass-card border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <i class="fa-solid fa-user-plus fa-3x text-indigo mb-3"></i>
                    <h2 class="fw-bold">Create Account</h2>
                    <p class="text-muted">Register as a customer to book and manage shipments.</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="register.php" method="POST" class="needs-validation" novalidate>
                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold small text-muted text-uppercase">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-regular fa-user"></i></span>
                            <input type="text" class="form-control" id="name" name="name" placeholder="John Doe" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small text-muted text-uppercase">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-regular fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="john@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Phone Number -->
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold small text-muted text-uppercase">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-phone"></i></span>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g. +1234567890" value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small text-muted text-uppercase">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Min. 6 characters" minlength="6" required>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-semibold small text-muted text-uppercase">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-lock-open"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fs-5">
                        <i class="fa-solid fa-user-check me-2"></i> Register Account
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-muted mb-0">Already have an account? <a href="login.php" class="text-indigo fw-semibold">Login here</a></p>
                </div>
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
