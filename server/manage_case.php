<?php
// Clean JSON API with output buffering
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean(); echo json_encode(['success' => false, 'message' => 'Tylko metoda POST jest dozwolona']); ob_end_flush();
    exit;
}

$action = $_POST['action'] ?? '';
$case_id = intval($_POST['case_id'] ?? 0);

if (empty($action)) {
    ob_clean(); echo json_encode(['success' => false, 'message' => 'Brak akcji']); ob_end_flush();
    exit;
}

try {
    switch ($action) {
        case 'add':
            // Create new case
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $rarity = trim($_POST['rarity'] ?? 'common');
            $image_url = trim($_POST['image'] ?? '');
            $status = ($_POST['status'] ?? 'active');
            $is_active = $status === 'active' ? 1 : 0;

            if ($name === '' || $price <= 0) {
                ob_clean(); echo json_encode(['success' => false, 'message' => 'Wymagane: nazwa i dodatnia cena']); ob_end_flush();
                exit;
            }

            // Unique name check
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM cases WHERE name = ?");
            if ($check_stmt) {
                mysqli_stmt_bind_param($check_stmt, 's', $name);
                mysqli_stmt_execute($check_stmt);
                $check_res = mysqli_stmt_get_result($check_stmt);
                if ($check_res && mysqli_num_rows($check_res) > 0) {
                    ob_clean(); echo json_encode(['success' => false, 'message' => 'Skrzynka o tej nazwie już istnieje']); ob_end_flush();
                    exit;
                }
            }

            $stmt = mysqli_prepare($conn, "INSERT INTO cases (name, description, price, rarity, image_url, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            if (!$stmt) {
                ob_clean(); echo json_encode(['success' => false, 'message' => 'Błąd bazy danych']); ob_end_flush();
                exit;
            }
            mysqli_stmt_bind_param($stmt, 'ssdssi', $name, $description, $price, $rarity, $image_url, $is_active);
            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($conn);
                ob_clean(); echo json_encode(['success' => true, 'message' => 'Skrzynka została dodana', 'case' => [
                    'id' => $new_id,
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'rarity' => $rarity,
                    'image_url' => $image_url,
                    'is_active' => $is_active
                ]]); ob_end_flush();
            } else {
                ob_clean(); echo json_encode(['success' => false, 'message' => 'Nie udało się dodać skrzynki']); ob_end_flush();
            }
            break;
        case 'toggle_status':
            // Przełącz status aktywny/nieaktywny
            $current_status_query = "SELECT is_active FROM cases WHERE id = ?";
            $stmt = mysqli_prepare($conn, $current_status_query);
            mysqli_stmt_bind_param($stmt, 'i', $case_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) === 0) {
                echo json_encode(['success' => false, 'message' => 'Skrzynka nie została znaleziona']);
                exit;
            }
            
            $case = mysqli_fetch_assoc($result);
            $new_status = ($case['is_active'] == 1) ? 0 : 1;
            
            $update_stmt = mysqli_prepare($conn, "UPDATE cases SET is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, 'ii', $new_status, $case_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Status skrzynki został zmieniony',
                    'new_status' => ($new_status == 1 ? 'active' : 'inactive')
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Błąd podczas zmiany statusu']);
            }
            break;
            
        case 'delete':
            // Usuń skrzynkę
            if (confirm_delete()) {
                // Najpierw usuń powiązane otwarcia skrzynek
                $delete_openings = mysqli_prepare($conn, "DELETE FROM case_openings WHERE case_id = ?");
                mysqli_stmt_bind_param($delete_openings, 'i', $case_id);
                mysqli_stmt_execute($delete_openings);
                
                // Następnie usuń skrzynkę
                $delete_case = mysqli_prepare($conn, "DELETE FROM cases WHERE id = ?");
                mysqli_stmt_bind_param($delete_case, 'i', $case_id);
                
                if (mysqli_stmt_execute($delete_case)) {
                    ob_clean(); echo json_encode([
                        'success' => true, 
                        'message' => 'Skrzynka została usunięta'
                    ]); ob_end_flush();
                } else {
                    ob_clean(); echo json_encode(['success' => false, 'message' => 'Błąd podczas usuwania skrzynki']); ob_end_flush();
                }
            } else {
                ob_clean(); echo json_encode(['success' => false, 'message' => 'Operacja anulowana']); ob_end_flush();
            }
            break;
            
        case 'edit':
            // Edytuj skrzynkę
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $rarity = trim($_POST['rarity'] ?? 'common');
            $image_url = trim($_POST['image'] ?? '');
            $status = ($_POST['status'] ?? 'active');
            $is_active = $status === 'active' ? 1 : 0;
            
            // Walidacja
            if ($case_id <= 0) {
                ob_clean(); echo json_encode(['success' => false, 'message' => 'Nieprawidłowe ID skrzynki']); ob_end_flush();
                exit;
            }
            if (empty($name) || $price <= 0) {
                ob_clean(); echo json_encode(['success' => false, 'message' => 'Wymagane: nazwa i dodatnia cena']); ob_end_flush();
                exit;
            }
            
            // Sprawdź czy nazwa już istnieje (oprócz tej edytowanej)
            $check_stmt = mysqli_prepare($conn, "SELECT id FROM cases WHERE name = ? AND id != ?");
            mysqli_stmt_bind_param($check_stmt, 'si', $name, $case_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                ob_clean(); echo json_encode(['success' => false, 'message' => 'Skrzynka o tej nazwie już istnieje']); ob_end_flush();
                exit;
            }
            
            // Aktualizuj skrzynkę
            $update_stmt = mysqli_prepare($conn, "UPDATE cases SET name = ?, description = ?, price = ?, rarity = ?, image_url = ?, is_active = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, 'ssdssii', $name, $description, $price, $rarity, $image_url, $is_active, $case_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                ob_clean(); echo json_encode([
                    'success' => true, 
                    'message' => 'Skrzynka została zaktualizowana',
                    'case' => [
                        'id' => $case_id,
                        'name' => $name,
                        'description' => $description,
                        'price' => $price,
                        'rarity' => $rarity,
                        'image_url' => $image_url,
                        'is_active' => $is_active
                    ]
                ]); ob_end_flush();
            } else {
                ob_clean(); echo json_encode(['success' => false, 'message' => 'Błąd podczas aktualizacji skrzynki']); ob_end_flush();
            }
            break;
            
        default:
            ob_clean(); echo json_encode(['success' => false, 'message' => 'Nieznana akcja']); ob_end_flush();
            break;
    }
    
} catch (Exception $e) {
    ob_clean(); echo json_encode(['success' => false, 'message' => 'Błąd serwera']); ob_end_flush();
}

function confirm_delete() {
    // W rzeczywistej aplikacji można dodać dodatkowe sprawdzenia
    return true;
}
?>
