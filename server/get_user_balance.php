<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Always return valid JSON
try {
    $balance = 0.00;
    $message = 'Default balance';
    
    if (isset($_SESSION['user_unique']) && !empty($_SESSION['user_unique'])) {
        ob_start();
        require_once 'conn.php';
        ob_end_clean();
        
        if (isset($conn) && $conn) {
            $user_unique = $_SESSION['user_unique'];
            $query = "SELECT total_balance FROM user_details WHERE user_unique = ?";
            $stmt = $conn->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param('s', $user_unique);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $user_data = $result->fetch_assoc();
                    $balance = floatval($user_data['total_balance'] ?? 0);
                    $message = 'User balance loaded from database';
                } else {
                    $balance = 0.00;
                    $message = 'User not found in database';
                }
            }
        }
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'balance' => $balance,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => true,
        'balance' => 0.00,
        'message' => 'Fallback balance'
    ]);
}
ob_end_flush();
exit();
?>
