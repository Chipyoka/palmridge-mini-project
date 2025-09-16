<?php
/**
 * Admin Logout using separate session name
 * Only destroys the admin session
 */

// Give the admin session a unique name
session_name('ADMINSESSID');
session_start();

// Unset all admin session variables
$_SESSION = [];

// Delete the admin session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000, 
        $params['path'], 
        $params['domain'], 
        $params['secure'], 
        $params['httponly']
    );
}

// Destroy the admin session file
session_destroy();

// Optional: start a fresh session for other users (no interference)
session_start();
session_regenerate_id(true);

// Redirect to admin login
header("Location: /admin/login.php");
exit;
