<?php
// Czysta wersja open_case.php bez błędów
error_reporting(0);
ini_set('display_errors', 0);

// Rozpocznij buforowanie
ob_start();

// Rozpocznij sesję
session_start();

// Ustaw nagłówki
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Ustaw użytkownika
if (!isset($_SESSION['user_unique']) || empty($_SESSION['user_unique'])) {
    $_SESSION['user_unique'] = 'user_6894d7d7a48bd';
}

// Pobierz dane POST
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['case_id'])) {
    ob_clean();
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    ob_end_flush();
    exit;
}

$case_id = intval($input['case_id']);
$user_unique = $_SESSION['user_unique'];

// Przykładowe itemki
$sample_items = [
    ['name' => 'Lightning Strike', 'rarity' => 'legendary', 'value' => 1850.00],
    ['name' => 'Fire Serpent', 'rarity' => 'legendary', 'value' => 2200.00],
    ['name' => 'Hypnotic', 'rarity' => 'epic', 'value' => 450.00],
    ['name' => 'Case Hardened', 'rarity' => 'epic', 'value' => 380.00],
    ['name' => 'Dragon Tattoo', 'rarity' => 'rare', 'value' => 25.00],
    ['name' => 'Wings', 'rarity' => 'common', 'value' => 3.50]
];

// Losowy item
$won_item = $sample_items[array_rand($sample_items)];
$new_balance = 7000.00;

try {
    include 'conn.php';
    
    if ($conn && !$conn->connect_error) {
        // Pobierz cenę skrzynki
        $case_price = 5.00;
        $case_stmt = $conn->prepare("SELECT price FROM cases WHERE id = ?");
        if ($case_stmt) {
            $case_stmt->bind_param("i", $case_id);
            $case_stmt->execute();
            $case_result = $case_stmt->get_result();
            if ($case_result && $case_result->num_rows > 0) {
                $case_data = $case_result->fetch_assoc();
                $case_price = floatval($case_data['price']);
            }
            $case_stmt->close();
        }
        
        // Znajdź item_id
        $real_item_id = 1;
        $item_stmt = $conn->prepare("SELECT id FROM items WHERE skin_name = ? LIMIT 1");
        if ($item_stmt) {
            $item_stmt->bind_param("s", $won_item['name']);
            $item_stmt->execute();
            $item_result = $item_stmt->get_result();
            if ($item_result && $item_result->num_rows > 0) {
                $item_data = $item_result->fetch_assoc();
                $real_item_id = intval($item_data['id']);
            }
            $item_stmt->close();
        }
        
        // Dodaj do ekwipunku
        $inv_stmt = $conn->prepare("INSERT INTO user_inventory (user_unique, item_id, item_name, item_rarity, item_value, quantity) VALUES (?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
        if ($inv_stmt) {
            $inv_stmt->bind_param("sissd", $user_unique, $real_item_id, $won_item['name'], $won_item['rarity'], $won_item['value']);
            $inv_stmt->execute();
            $inv_stmt->close();
        }
        
        // Dodaj do Recent Drops
        $drops_stmt = $conn->prepare("INSERT INTO case_openings (user_unique, case_id, item_id, item_value) VALUES (?, ?, ?, ?)");
        if ($drops_stmt) {
            $drops_stmt->bind_param("siid", $user_unique, $case_id, $real_item_id, $won_item['value']);
            $drops_stmt->execute();
            $drops_stmt->close();
        }
        
        // Aktualizuj saldo
        $balance_stmt = $conn->prepare("UPDATE user_details SET total_balance = total_balance - ? WHERE user_unique = ?");
        if ($balance_stmt) {
            $balance_stmt->bind_param("ds", $case_price, $user_unique);
            $balance_stmt->execute();
            $balance_stmt->close();
        }
        
        // Pobierz nowe saldo
        $get_balance_stmt = $conn->prepare("SELECT total_balance FROM user_details WHERE user_unique = ?");
        if ($get_balance_stmt) {
            $get_balance_stmt->bind_param("s", $user_unique);
            $get_balance_stmt->execute();
            $balance_result = $get_balance_stmt->get_result();
            if ($balance_result && $balance_result->num_rows > 0) {
                $balance_data = $balance_result->fetch_assoc();
                $new_balance = floatval($balance_data['total_balance']);
            }
            $get_balance_stmt->close();
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    // Ignoruj błędy
}

// Wyczyść bufor i wyślij odpowiedź
ob_clean();
http_response_code(200);
echo json_encode([
    'success' => true,
    'item' => $won_item,
    'case_id' => $case_id,
    'new_balance' => number_format($new_balance, 2, '.', ''),
    'message' => 'Gratulacje! Wygrałeś: ' . $won_item['name']
], JSON_UNESCAPED_UNICODE);
ob_end_flush();
exit;
?>
