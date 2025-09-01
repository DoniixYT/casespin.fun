<?php
// Steam API Configuration
// Get your Steam Web API key from: https://steamcommunity.com/dev/apikey
$steam_api_key = '8F0871F550544DE3BF1FA258AF4D5953'; // Replace with your actual Steam API key

// Steam OpenID Configuration
$steam_login_url = 'https://steamcommunity.com/openid/login';

// Auto-detect current domain and path for Steam URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$base_path = str_replace('/server', '', $script_path);
$base_path = str_replace('/components', '', $base_path);

$steam_return_url = $protocol . '://' . $host . $base_path . '/server/steam_callback.php';
$steam_realm = $protocol . '://' . $host . $base_path . '/';

// Note: Update the URLs above for production environment

// Helper function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function to escape output (without database connection)
function escape_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
?>
