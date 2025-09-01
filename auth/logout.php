<?php
// Unified logout endpoint for site and admin
// Destroys session and redirects to login

// Start buffering to avoid any output before headers
ob_start();

// Start session if not started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Delete the session cookie if set
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

// Destroy session
session_destroy();

// Optionally clear custom auth cookies (uncomment/adjust if used)
// setcookie('remember_token', '', time() - 3600, '/');

// Redirect to home page
header('Location: /');
ob_end_flush();
exit;
