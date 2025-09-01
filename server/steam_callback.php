<?php
session_start();
include 'conn.php';

// Function to validate Steam OpenID response
function validateSteamLogin() {
    $params = array(
        'openid.assoc_handle' => $_GET['openid_assoc_handle'],
        'openid.signed' => $_GET['openid_signed'],
        'openid.sig' => $_GET['openid_sig'],
        'openid.ns' => 'http://specs.openid.net/auth/2.0',
        'openid.mode' => 'check_authentication'
    );
    
    $signed = explode(',', $_GET['openid_signed']);
    foreach($signed as $item) {
        $val = $_GET['openid_' . str_replace('.', '_', $item)];
        $params['openid.' . $item] = $val;
    }
    
    $data = http_build_query($params);
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => "Accept-language: en\r\n" .
                       "Content-type: application/x-www-form-urlencoded\r\n" .
                       "Content-length: " . strlen($data) . "\r\n",
            'content' => $data
        )
    ));
    
    $result = file_get_contents('https://steamcommunity.com/openid/login', false, $context);
    return preg_match("/is_valid\s*:\s*true/i", $result);
}

// Function to get Steam ID from OpenID identity
function getSteamID($openid_identity) {
    return str_replace('https://steamcommunity.com/openid/id/', '', $openid_identity);
}

// Function to get Steam user info
function getSteamUserInfo($steam_id) {
    global $steam_api_key;
    
    // Check if Steam API key is configured
    if ($steam_api_key === 'YOUR_STEAM_API_KEY_HERE' || empty($steam_api_key)) {
        return null; // API key not configured
    }
    
    $url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$steam_api_key}&steamids={$steam_id}";
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (isset($data['response']['players'][0])) {
        return $data['response']['players'][0];
    }
    
    return null;
}

// Process Steam login callback
if (isset($_GET['openid_mode']) && $_GET['openid_mode'] === 'id_res') {
    if (validateSteamLogin()) {
        $steam_id = getSteamID($_GET['openid_identity']);
        
        // For now, we'll create a basic user profile without Steam API
        // In production, you should get Steam API key and fetch user details
        
        // Check if user exists in database
        $stmt = $conn->prepare("SELECT * FROM user_details WHERE steam_id = ?");
        $stmt->bind_param("s", $steam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // User exists, log them in
            $user = $result->fetch_assoc();
            $_SESSION['user_unique'] = $user['user_unique'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['steam_id'] = $steam_id;
            
            // Set login cookie
            setcookie('cashplay_is_login', 'true', time() + (86400 * 30), '/');
            
        } else {
            // New user, create account
            $user_unique = 'user_' . uniqid();
            
            // Try to get Steam user info
            $steam_user_info = getSteamUserInfo($steam_id);
            
            if ($steam_user_info) {
                // Use Steam API data
                $username = $steam_user_info['personaname'] ?? 'SteamUser_' . substr($steam_id, -6);
                $avatar = $steam_user_info['avatarfull'] ?? $steam_user_info['avatarmedium'] ?? $steam_user_info['avatar'] ?? null;
            } else {
                // Fallback to default values
                $username = 'SteamUser_' . substr($steam_id, -6);
                $avatar = null;
            }
            
            // If no Steam avatar, use UI Avatars as fallback
            if (!$avatar) {
                $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&background=7c3aed&color=fff';
            }
            
            // Generate unique phone_number placeholder for Steam users
            $phone_placeholder = 'steam_' . $steam_id;
            $balance = 0;
            $current_time = date('Y-m-d H:i:s');
            
            $stmt = $conn->prepare("INSERT INTO user_details (user_unique, username, steam_id, avatar, total_balance, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssds", $user_unique, $username, $steam_id, $avatar, $balance, $current_time);
            
            if ($stmt->execute()) {
                $_SESSION['user_unique'] = $user_unique;
                $_SESSION['username'] = $username;
                $_SESSION['steam_id'] = $steam_id;
                
                // Set login cookie
                setcookie('cashplay_is_login', 'true', time() + (86400 * 30), '/');
            }
        }
        
        // Redirect to main page
        header('Location: ../index.php');
        exit();
        
    } else {
        // Steam validation failed
        header('Location: ../index.php?error=steam_validation_failed');
        exit();
    }
} else {
    // Invalid callback
    header('Location: ../index.php?error=invalid_steam_callback');
    exit();
}
?>
