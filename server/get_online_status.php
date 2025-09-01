<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include 'conn.php';

$users = [];

// Pobierz użytkowników z bazy
$sql = "SELECT user_unique, username FROM user_details LIMIT 10";
$result = mysqli_query($conn, $sql);

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        // Sprawdź status online - aktywność w ostatnich 5 minutach
        $online_sql = "SELECT COUNT(*) as count FROM login_activity 
                      WHERE la_user_unique = ? 
                      AND la_login_time > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
        
        $online_stmt = mysqli_prepare($conn, $online_sql);
        $is_online = false;
        
        if ($online_stmt) {
            mysqli_stmt_bind_param($online_stmt, 's', $row['user_unique']);
            mysqli_stmt_execute($online_stmt);
            $online_result = mysqli_stmt_get_result($online_stmt);
            $online_data = mysqli_fetch_assoc($online_result);
            $is_online = (intval($online_data['count']) > 0);
            mysqli_stmt_close($online_stmt);
        }
        
        $users[] = [
            'user_unique' => $row['user_unique'],
            'username' => $row['username'],
            'is_online' => $is_online
        ];
    }
}

echo json_encode([
    'success' => true,
    'users' => $users,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
