<?php
/**
 * Authentication & Helper Functions
 * Courier & Parcel Tracking System
 */

// Include config if not already included (which handles sessions)
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Check if a user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Guard for Customer pages
 */
function guard_customer() {
    if (!is_logged_in()) {
        set_flash_message('danger', 'Please log in to access this page.');
        header("Location: ../login.php");
        exit();
    }
    if ($_SESSION['user_role'] !== 'customer') {
        set_flash_message('danger', 'Unauthorized access.');
        header("Location: ../login.php");
        exit();
    }
    
    // Check if blocked
    global $conn;
    $user_id = $_SESSION['user_id'];
    $check = mysqli_query($conn, "SELECT status FROM users WHERE id = $user_id LIMIT 1");
    if ($check && $row = mysqli_fetch_assoc($check)) {
        if ($row['status'] === 'blocked') {
            session_destroy();
            session_start();
            set_flash_message('danger', 'Your account has been blocked by the administrator.');
            header("Location: ../login.php");
            exit();
        }
    }
}

/**
 * Guard for Admin pages
 */
function guard_admin() {
    if (!is_logged_in()) {
        set_flash_message('danger', 'Please log in to access this page.');
        header("Location: ../login.php");
        exit();
    }
    if ($_SESSION['user_role'] !== 'admin') {
        set_flash_message('danger', 'Unauthorized access to Admin Panel.');
        header("Location: ../login.php");
        exit();
    }
}

/**
 * Guard for Rider pages
 */
function guard_rider() {
    if (!isset($_SESSION['rider_id'])) {
        set_flash_message('danger', 'Please log in as a Rider to access this page.');
        header("Location: ../login.php");
        exit();
    }
    
    // Check if inactive
    global $conn;
    $rider_id = $_SESSION['rider_id'];
    $check = mysqli_query($conn, "SELECT status FROM riders WHERE id = $rider_id LIMIT 1");
    if ($check && $row = mysqli_fetch_assoc($check)) {
        if ($row['status'] === 'inactive') {
            session_destroy();
            session_start();
            set_flash_message('danger', 'Your rider account is currently inactive.');
            header("Location: ../login.php");
            exit();
        }
    }
}

/**
 * Set flash message in session
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, // success, danger, warning, info
        'text' => $message
    ];
}

/**
 * Display flash message if it exists
 */
function display_flash_messages() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        echo '<div class="alert alert-' . htmlspecialchars($msg['type']) . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($msg['text']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
        unset($_SESSION['flash_msg']);
    }
}

/**
 * Get CSS badge class for status
 */
function get_badge_class($status) {
    switch ($status) {
        case 'Pending':
            return 'badge-pending';
        case 'Assigned':
            return 'badge-assigned';
        case 'Picked Up':
            return 'badge-pickedup';
        case 'In Transit':
            return 'badge-intransit';
        case 'Delivered':
            return 'badge-delivered';
        default:
            return 'bg-secondary text-white';
    }
}

/**
 * Generate a unique tracking ID
 */
function generate_tracking_id($conn) {
    $exists = true;
    $tracking_id = '';
    
    while ($exists) {
        $rand_num = rand(10000000, 99999999);
        $tracking_id = 'TRK' . $rand_num;
        
        $query = "SELECT id FROM parcels WHERE tracking_id = '$tracking_id' LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) === 0) {
            $exists = false;
        }
    }
    
    return $tracking_id;
}

/**
 * Sanitize user input
 */
function sanitize($conn, $input) {
    return mysqli_real_escape_string($conn, trim($input));
}
?>
