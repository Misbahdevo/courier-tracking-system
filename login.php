<?php
/**
 * Unified Login Portal
 * Courier & Parcel Tracking System
 */

$page_title = "Login - SwiftPost";
$path_prefix = "./";
require_once $path_prefix . 'includes/header.php';

// Redirect if already logged in
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit();
    } else {
        header("Location: customer/dashboard.php");
        exit();
    }
} elseif (isset($_SESSION['rider_id'])) {
    header("Location: rider/dashboard.php");
    exit();
}

$login_input = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = sanitize($conn, $_POST['login_input']);
    $password = $_POST['password'];
    
    if (empty($login_input) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // 1. Search in users table (Customers and Admin)
        $user_query = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $user_query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $login_input);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($user = mysqli_fetch_assoc($result)) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Check if blocked
                    if ($user['status'] === 'blocked') {
                        $error = "Your account has been blocked by the administrator.";
                    } else {
                        // Set Session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_name'] = $user['name'];
                        
                        set_flash_message('success', 'Welcome back, ' . $user['name'] . '!');
                        
                        if ($user['role'] === 'admin') {
                            header("Location: admin/dashboard.php");
                        } else {
                            header("Location: customer/dashboard.php");
                        }
                        exit();
                    }
                } else {
                    $error = "Invalid Email or Password.";
                }
            } else {
                // 2. Search in riders table
                $rider_query = "SELECT * FROM riders WHERE username = ? LIMIT 1";
                $rider_stmt = mysqli_prepare($conn, $rider_query);
                
                if ($rider_stmt) {
                    mysqli_stmt_bind_param($rider_stmt, "s", $login_input);
                    mysqli_stmt_execute($rider_stmt);
                    $rider_result = mysqli_stmt_get_result($rider_stmt);
                    
                    if ($rider = mysqli_fetch_assoc($rider_result)) {
                        // Verify password
                        if (password_verify($password, $rider['password'])) {
                            // Check if inactive
                            if ($rider['status'] === 'inactive') {
                                $error = "Your rider account is currently inactive.";
                            } else {
                                // Set Session
                                $_SESSION['rider_id'] = $rider['id'];
                                $_SESSION['rider_name'] = $rider['name'];
                                
                                set_flash_message('success', 'Rider Portal loaded. Good luck today!');
                                header("Location: rider/dashboard.php");
                                exit();
                            }
                        } else {
                            $error = "Invalid Username or Password.";
                        }
                    } else {
                        $error = "Account not found. Please register or contact Admin.";
                    }
                    mysqli_stmt_close($rider_stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="row justify-content-center my-5">
    <div class="col-md-8 col-lg-5">
        <div class="card glass-card border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <i class="fa-solid fa-lock fa-3x text-indigo mb-3"></i>
                    <h2 class="fw-bold">Sign In</h2>
                    <p class="text-muted">Enter your credentials to access your dashboard.</p>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST">
                    <!-- Email or Username -->
                    <div class="mb-3">
                        <label for="login_input" class="form-label fw-semibold small text-muted text-uppercase">Email / Username</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">
                                <i class="fa-regular fa-user"></i>
                            </span>
                            <input type="text" class="form-control" id="login_input" name="login_input" placeholder="admin@courier.com or rider_john" value="<?php echo htmlspecialchars($login_input); ?>" required>
                        </div>
                        <div class="form-text small text-muted">Admins/Customers use email. Riders use username.</div>
                    </div>
                    
                    <!-- Password -->
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold small text-muted text-uppercase">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">
                                <i class="fa-solid fa-key"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 fs-5">
                        <i class="fa-solid fa-right-to-bracket me-2"></i> Login
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <p class="text-muted mb-0">Don't have an account? <a href="register.php" class="text-indigo fw-semibold">Register as Customer</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once $path_prefix . 'includes/footer.php'; 
?>
