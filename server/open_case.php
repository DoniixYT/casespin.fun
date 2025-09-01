<?php
// Włącz pełne debugowanie, żeby zobaczyć wszystkie błędy
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Rozpocznij buforowanie wyjścia, żeby przechwycić ewentualne ostrzeżenia
ob_start();

// Nowy, potężny backend do otwierania skrzynek

// Start sesji i podstawowa konfiguracja
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Dołączanie połączenia z bazą danych
require_once 'conn.php';

// Sprytne tworzenie tabeli ekwipunku, jeśli nie istnieje
$create_inventory_table_sql = ""
    . 'CREATE TABLE IF NOT EXISTS `user_inventory` ('
    . '  `id` INT AUTO_INCREMENT PRIMARY KEY,'
    . '  `user_unique` VARCHAR(255) NOT NULL,'
    . '  `item_id` VARCHAR(255) NOT NULL,'
    . '  `status` VARCHAR(50) NOT NULL DEFAULT \'in_inventory\','
    . '  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,'
    . '  FOREIGN KEY (`user_unique`) REFERENCES `user_details`(`user_unique`) ON DELETE CASCADE,'
    . '  FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE CASCADE'
    . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';

if (!$conn->query($create_inventory_table_sql)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Błąd krytyczny: Nie można utworzyć tabeli ekwipunku. ' . $conn->error]);
    exit;
}

// Upewnij się, że kolumna 'status' istnieje, na wypadek gdyby tabela była stworzona wcześniej bez niej
$check_column_sql = "SHOW COLUMNS FROM `user_inventory` LIKE 'status'";
$result = $conn->query($check_column_sql);
if ($result && $result->num_rows == 0) {
    // Kolumna nie istnieje, dodaj ją
    $alter_table_sql = "ALTER TABLE `user_inventory` ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'in_inventory'";
    if (!$conn->query($alter_table_sql)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Błąd krytyczny: Nie można zaktualizować struktury tabeli ekwipunku. ' . $conn->error]);
        exit;
    }
}

// Upewnij się, że błędny unikalny klucz 'user_item_unique' nie istnieje
$check_key_sql = "SHOW INDEX FROM `user_inventory` WHERE Key_name = 'user_item_unique'";
$result_key = $conn->query($check_key_sql);
if ($result_key && $result_key->num_rows > 0) {
    // Klucz istnieje, usuń go
    $drop_key_sql = "ALTER TABLE `user_inventory` DROP INDEX `user_item_unique`";
    if (!$conn->query($drop_key_sql)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Błąd krytyczny: Nie można usunąć błędnego indeksu z tabeli ekwipunku. ' . $conn->error]);
        exit;
    }
}

// Sprawdź i napraw typ kolumny item_id w case_openings
$check_column_type_sql = "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE table_schema = DATABASE() AND table_name = 'case_openings' AND column_name = 'item_id'";
$result_type = $conn->query($check_column_type_sql);
if ($result_type && $result_type->num_rows > 0) {
    $row = $result_type->fetch_assoc();
    if (strtolower($row['DATA_TYPE']) == 'int') {
        // Typ kolumny jest nieprawidłowy, zmień go
        $alter_log_table_sql = "ALTER TABLE `case_openings` MODIFY COLUMN `item_id` VARCHAR(50) NOT NULL";
        if (!$conn->query($alter_log_table_sql)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Błąd krytyczny: Nie można naprawić struktury tabeli logów. ' . $conn->error]);
            exit;
        }
    }
}

// Pobranie danych wejściowych
$input = json_decode(file_get_contents('php://input'), true);
$case_id = isset($input['case_id']) ? intval($input['case_id']) : 0;

if ($case_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowe ID skrzynki.']);
    exit;
}

// Sprawdzenie, czy użytkownik jest "zalogowany"
if (!isset($_SESSION['user_unique']) || empty($_SESSION['user_unique'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Użytkownik niezalogowany.']);
    exit;
}
$user_unique = $_SESSION['user_unique'];

// Rozpoczęcie transakcji
$conn->begin_transaction();

try {
    // 1. Pobierz dane skrzynki (cena)
    $stmt_case = $conn->prepare("SELECT price FROM cases WHERE id = ?");
    $stmt_case->bind_param("i", $case_id);
    $stmt_case->execute();
    $result_case = $stmt_case->get_result();
    if ($result_case->num_rows === 0) {
        throw new Exception('Skrzynka nie istnieje.');
    }
    $case_data = $result_case->fetch_assoc();
    $case_price = floatval($case_data['price']);
    $stmt_case->close();

    // 2. Pobierz dane użytkownika i zablokuj wiersz do aktualizacji
    $stmt_user = $conn->prepare("SELECT total_balance FROM user_details WHERE user_unique = ? FOR UPDATE");
    $stmt_user->bind_param("s", $user_unique);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows === 0) {
        throw new Exception('Użytkownik nie istnieje.');
    }
    $user_data = $result_user->fetch_assoc();
    $user_balance = floatval($user_data['total_balance']);
    $stmt_user->close();

    // 3. Sprawdź, czy użytkownika stać
    if ($user_balance < $case_price) {
        throw new Exception('Niewystarczające środki. Twoje saldo: $' . number_format($user_balance, 2) . ', koszt skrzynki: $' . number_format($case_price, 2));
    }

    // 4. Zaktualizuj saldo użytkownika
    $new_balance = $user_balance - $case_price;
    $stmt_update_balance = $conn->prepare("UPDATE user_details SET total_balance = ? WHERE user_unique = ?");
    $stmt_update_balance->bind_param("ds", $new_balance, $user_unique);
    $stmt_update_balance->execute();
    $stmt_update_balance->close();

    // 5. Pobierz możliwe przedmioty i wylosuj jeden
    $stmt_items = $conn->prepare("SELECT i.item_id, i.skin_name, i.weapon_name, i.rarity, i.price, i.image_url, ci.drop_rate FROM case_items ci JOIN items i ON ci.item_id = i.item_id WHERE ci.case_id = ?");
    $stmt_items->bind_param("i", $case_id);
    $stmt_items->execute();
    $possible_items = $stmt_items->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_items->close();

    if (empty($possible_items)) {
        throw new Exception('Brak przedmiotów w tej skrzynce.');
    }

    // Logika ważonego losowania
    $total_weight = array_sum(array_column($possible_items, 'drop_rate'));
    $random_value = (mt_rand() / mt_getrandmax()) * $total_weight;
    $current_weight = 0;
    $won_item_data = null;

    foreach ($possible_items as $item) {
        $current_weight += floatval($item['drop_rate']);
        if ($random_value <= $current_weight) {
            $won_item_data = $item;
            break;
        }
    }

    if (!$won_item_data) {
        // Fallback na ostatni przedmiot, jeśli coś pójdzie nie tak
        $won_item_data = end($possible_items);
    }

    // 6. Dodaj wygrany przedmiot do ekwipunku użytkownika (Tabela `user_inventory` musi istnieć!)
    $stmt_add_inventory = $conn->prepare("INSERT INTO user_inventory (user_unique, item_id, status) VALUES (?, ?, 'in_inventory')");
    $stmt_add_inventory->bind_param("ss", $user_unique, $won_item_data['item_id']);
    $stmt_add_inventory->execute();
    $inventory_id = $stmt_add_inventory->insert_id;
    $stmt_add_inventory->close();

    // 7. Zapisz log otwarcia skrzynki
    $stmt_log = $conn->prepare("INSERT INTO case_openings (user_unique, case_id, item_id, item_value) VALUES (?, ?, ?, ?)");
    $stmt_log->bind_param("sisd", $user_unique, $case_id, $won_item_data['item_id'], $won_item_data['price']);
    $stmt_log->execute();
    $stmt_log->close();

    // Zatwierdzenie transakcji
    $conn->commit();

    // Przygotowanie odpowiedzi
    $weapon = trim($won_item_data['weapon_name'] ?? '');
    $skin = trim($won_item_data['skin_name'] ?? '');
    $full_name = $weapon !== '' && $skin !== '' ? ($weapon . ' | ' . $skin) : ($weapon ?: $skin);

    $response_item = [
        'inventory_id' => $inventory_id,
        'item_id' => $won_item_data['item_id'],
        'name' => $full_name,
        'rarity' => strtolower($won_item_data['rarity'] ?? 'common'),
        'price' => floatval($won_item_data['price'] ?? 0.0),
        'image' => $won_item_data['image_url'] ?? 'assets/img/logo4.png'
    ];

    // Wyczyść bufor przed zwróceniem JSON
    ob_clean();
    http_response_code(200); // Jawnie ustaw kod sukcesu
    echo json_encode([
        'success' => true,
        'item' => $response_item,
        'new_balance' => $new_balance
    ]);

} catch (Exception $e) {
    // Wycofanie transakcji w przypadku błędu
    $conn->rollback();
    // Wyczyść bufor przed zwróceniem błędu
    ob_clean();
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>