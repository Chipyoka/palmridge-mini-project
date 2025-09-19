<?php
/**
 * User Logout using separate session name
 * Only destroys the User session
 */

// Give the User session a unique name
session_name('USERSESSID');
session_start();

// Unset all User session variables
$_SESSION = [];

// Delete the User session cookie
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

// Destroy the User session file
session_destroy();

// Optional: start a fresh session for other users (no interference)
session_start();
session_regenerate_id(true);

// Redirect to User login
header("Location: /odl_mini_projects/zambezi-mini-project/index.php");
exit;
