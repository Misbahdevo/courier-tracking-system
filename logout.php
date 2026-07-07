<?php
/**
 * Logout Page
 * Courier & Parcel Tracking System
 */

require_once 'config.php';
require_once 'includes/auth_helper.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a clean session to set a logout flash message
session_start();
set_flash_message('info', 'You have been successfully logged out.');

// Redirect to login page
header("Location: login.php");
exit();
?>
