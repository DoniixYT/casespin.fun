<?php
session_start();
include 'conn.php';

header('Content-Type: application/json');

// TYMCZASOWO - symuluj zalogowanego użytkownika dla testów
if (!isset($_SESSION['user_unique']) || empty($_SESSION['user_unique'])) {
    $_SESSION['user_unique'] = 'Doniix';
}

$user_unique = $_SESSION['user_unique'];

// ZAWSZE zwróć testowe saldo - uproszczona wersja
try {
    // Próbuj pobrać z bazy, ale jeśli nie ma - ustaw testowe saldo
    if ($conn && !$conn->connect_error) {
        $balance_query = $conn->prepare("SELECT total_balance FROM user_details WHERE user_unique = ?");
        $balance_query->bind_param("s", $user_unique);
        $balance_query->execute();
        $balance_result = $balance_query->get_result();
        
        if ($balance_result && $balance_result->num_rows > 0) {
            $balance_data = $balance_result->fetch_assoc();
            $user_balance = floatval($balance_data['total_balance'] ?? 0);
            
            // Jeśli saldo jest 0, ustaw testowe saldo
            if ($user_balance <= 0) {
                $user_balance = 1000.00;
            }
        } else {
            // Użytkownik nie istnieje - ustaw testowe saldo
            $user_balance = 1000.00;
        }
        
        $balance_query->close();
    } else {
        // Brak połączenia z bazą - ustaw testowe saldo
        $user_balance = 1000.00;
    }
    
    echo json_encode([
        'success' => true,
        'balance' => number_format($user_balance, 2, '.', '')
    ]);
    
} catch (Exception $e) {
    // W przypadku błędu - zwróć testowe saldo
    echo json_encode([
        'success' => true,
        'balance' => '1000.00'
    ]);
}

if ($conn) {
    $conn->close();
}
?>
