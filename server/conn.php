<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
// Database configuration
$config = [
    'host' => 'sql313.infinityfree.com',
    'username' => 'if0_37268558',
    'password' => 'MXi0kBUuJ9Y',
    'database' => 'if0_37268558_casino',
    'port' => 3306,
    'charset' => 'utf8mb4'
];

// Create connection with error handling
$conn = new mysqli(
    $config['host'],
    $config['username'],
    $config['password'],
    $config['database'],
    $config['port']
);

// Check connection - just log error, don't die
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    $conn = null; // Set to null so scripts can handle fallback
}

// Set charset to ensure proper character encoding
if ($conn && !$conn->set_charset($config['charset'])) {
    error_log("Error loading character set {$config['charset']}: " . $conn->error);
}

// Set timezone
if ($conn) {
    $conn->query("SET time_zone = '+00:00'");
}

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

// Debug Steam configuration (remove in production)
// Debug output removed to prevent JSON corruption

// Function to safely execute a query
if (!function_exists('db_query')) {
    function db_query($sql, $params = []) {
        global $conn;
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
            $bindParams[] = $param;
        }
        
        array_unshift($bindParams, $types);
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    return $stmt;
}
}

// Register shutdown function to close database connection
register_shutdown_function(function() {
    global $conn;
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
});
?>