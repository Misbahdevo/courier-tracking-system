<?php
/**
 * Shared Header Layout
 * Courier & Parcel Tracking System
 */

// Define path prefix if not set
if (!isset($path_prefix)) {
    $path_prefix = './';
}

// Require connection and auth helper
require_once $path_prefix . 'config.php';
require_once $path_prefix . 'includes/auth_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Courier & Parcel Tracking'; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo $path_prefix; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $path_prefix; ?>index.php">
                <i class="fa-solid fa-truck-fast me-2 text-indigo"></i>
                <span class="fs-4">SwiftPost</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (isset($_SESSION['user_role'])): ?>
                        
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <!-- Admin Top Navbar Link -->
                            <li class="nav-item">
                                <a class="nav-link px-3 py-2 fw-semibold" href="<?php echo $path_prefix; ?>admin/dashboard.php">
                                    <i class="fa-solid fa-gauge me-1"></i> Admin Panel
                                </a>
                            </li>
                        <?php elseif ($_SESSION['user_role'] === 'customer'): ?>
                            <!-- Customer Navbar Links -->
                            <li class="nav-item">
                                <a class="nav-link px-3 py-2 fw-semibold" href="<?php echo $path_prefix; ?>customer/dashboard.php">
                                    <i class="fa-solid fa-box me-1"></i> My Parcels
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link px-3 py-2 fw-semibold" href="<?php echo $path_prefix; ?>customer/book.php">
                                    <i class="fa-solid fa-square-plus me-1"></i> Book Parcel
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Common Logged In Actions -->
                        <li class="nav-item ms-lg-3">
                            <div class="d-flex align-items-center">
                                <span class="navbar-text me-3 d-none d-lg-inline">
                                    Hi, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></strong>
                                </span>
                                <a class="btn btn-outline-danger btn-sm" href="<?php echo $path_prefix; ?>logout.php">
                                    <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                                </a>
                            </div>
                        </li>

                    <?php elseif (isset($_SESSION['rider_id'])): ?>
                        <!-- Rider Navbar Links -->
                        <li class="nav-item">
                            <a class="nav-link px-3 py-2 fw-semibold" href="<?php echo $path_prefix; ?>rider/dashboard.php">
                                <i class="fa-solid fa-motorcycle me-1"></i> Rider Dashboard
                            </a>
                        </li>
                        <li class="nav-item ms-lg-3">
                            <div class="d-flex align-items-center">
                                <span class="navbar-text me-3 d-none d-lg-inline">
                                    Rider: <strong><?php echo htmlspecialchars($_SESSION['rider_name'] ?? 'Rider'); ?></strong>
                                </span>
                                <a class="btn btn-outline-danger btn-sm" href="<?php echo $path_prefix; ?>logout.php">
                                    <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                                </a>
                            </div>
                        </li>
                        
                    <?php else: ?>
                        <!-- Guest Navbar Links -->
                        <li class="nav-item">
                            <a class="nav-link px-3 py-2 fw-semibold" href="<?php echo $path_prefix; ?>index.php">
                                <i class="fa-solid fa-magnifying-glass me-1"></i> Track Parcel
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="nav-link px-3 py-2 fw-semibold" href="<?php echo $path_prefix; ?>login.php">
                                <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                            </a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-primary btn-sm" href="<?php echo $path_prefix; ?>register.php">
                                <i class="fa-solid fa-user-plus me-1"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container py-4 flex-grow-1">
        <?php 
        // Display flash messages globally
        display_flash_messages(); 
        ?>
