<?php
session_start();
include 'conn.php';

// Function to initiate Steam login
function initiateSteamLogin() {
    global $steam_login_url, $steam_return_url, $steam_realm;
    
    // Debug: Check if variables are set
    if (empty($steam_login_url) || empty($steam_return_url) || empty($steam_realm)) {
        die('Steam configuration error: ' . 
            'login_url=' . ($steam_login_url ?? 'empty') . ', ' .
            'return_url=' . ($steam_return_url ?? 'empty') . ', ' .
            'realm=' . ($steam_realm ?? 'empty'));
    }
    
    $params = array(
        'openid.ns' => 'http://specs.openid.net/auth/2.0',
        'openid.mode' => 'checkid_setup',
        'openid.return_to' => $steam_return_url,
        'openid.realm' => $steam_realm,
        'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
        'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select'
    );
    
    $login_url = $steam_login_url . '?' . http_build_query($params);
    
    // Debug: Show the URL before redirect (remove this after testing)
    if (isset($_GET['debug'])) {
        echo '<h3>Steam Login Debug</h3>';
        echo '<strong>Login URL:</strong> ' . htmlspecialchars($login_url) . '<br>';
        echo '<strong>Return URL:</strong> ' . htmlspecialchars($steam_return_url) . '<br>';
        echo '<strong>Realm:</strong> ' . htmlspecialchars($steam_realm) . '<br>';
        echo '<br><a href="' . htmlspecialchars($login_url) . '">Click here to go to Steam</a>';
        exit();
    }
    
    header('Location: ' . $login_url);
    exit();
}

// Check if this is a login request
if (isset($_GET['action']) && $_GET['action'] === 'login') {
    initiateSteamLogin();
}

// If no action specified, redirect to main page
header('Location: ../index.php');
exit();
?>
