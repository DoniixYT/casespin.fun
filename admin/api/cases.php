<?php
// API endpoint do zarządzania skrzynkami
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($conn)) { 
    require_once __DIR__ . '/../config.php'; 
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if (!$conn) {
        throw new Exception('Brak połączenia z bazą danych');
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'get_all':
            // Pobierz wszystkie skrzynki ze statystykami
            $query = "SELECT c.*, 
                             COUNT(co.id) as open_count,
                             COALESCE(SUM(c.price), 0) as total_revenue
                      FROM cases c 
                      LEFT JOIN case_openings co ON c.id = co.case_id 
                      GROUP BY c.id
                      ORDER BY c.created_at DESC";
            
            $result = $conn->query($query);
            $cases = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $cases[] = $row;
                }
            }
            
            $response['success'] = true;
            $response['data'] = $cases;
            break;

        case 'create':
            // Dodaj nową skrzynkę
            $name = $conn->real_escape_string($_POST['name'] ?? '');
            $description = $conn->real_escape_string($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $image_url = $conn->real_escape_string($_POST['image_url'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (empty($name)) {
                throw new Exception('Nazwa skrzynki jest wymagana');
            }

            $query = "INSERT INTO cases (name, description, price, image_url, is_active, created_at) 
                      VALUES ('$name', '$description', $price, '$image_url', $is_active, NOW())";
            
            if ($conn->query($query)) {
                $case_id = $conn->insert_id;
                $response['success'] = true;
                $response['message'] = 'Skrzynka została dodana';
                $response['data'] = ['id' => $case_id];
            } else {
                throw new Exception('Błąd dodawania skrzynki: ' . $conn->error);
            }
            break;

        case 'update':
            // Aktualizuj skrzynkę
            $case_id = (int)($_POST['case_id'] ?? 0);
            $name = $conn->real_escape_string($_POST['name'] ?? '');
            $description = $conn->real_escape_string($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $image_url = $conn->real_escape_string($_POST['image_url'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($case_id <= 0) {
                throw new Exception('Nieprawidłowe ID skrzynki');
            }

            if (empty($name)) {
                throw new Exception('Nazwa skrzynki jest wymagana');
            }

            $query = "UPDATE cases SET 
                        name = '$name',
                        description = '$description', 
                        price = $price,
                        image_url = '$image_url',
                        is_active = $is_active
                      WHERE id = $case_id";
            
            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = 'Skrzynka została zaktualizowana';
            } else {
                throw new Exception('Błąd aktualizacji skrzynki: ' . $conn->error);
            }
            break;

        case 'delete':
            // Usuń skrzynkę
            $case_id = (int)($_POST['case_id'] ?? 0);

            if ($case_id <= 0) {
                throw new Exception('Nieprawidłowe ID skrzynki');
            }

            // Usuń najpierw powiązane drops i openings
            $conn->query("DELETE FROM case_items WHERE case_id = $case_id");
            $conn->query("DELETE FROM case_openings WHERE case_id = $case_id");
            
            // Usuń skrzynkę
            $query = "DELETE FROM cases WHERE id = $case_id";
            
            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = 'Skrzynka została usunięta';
            } else {
                throw new Exception('Błąd usuwania skrzynki: ' . $conn->error);
            }
            break;

        case 'toggle_active':
            // Przełącz status aktywności
            $case_id = (int)($_POST['case_id'] ?? 0);

            if ($case_id <= 0) {
                throw new Exception('Nieprawidłowe ID skrzynki');
            }

            $query = "UPDATE cases SET is_active = NOT is_active WHERE id = $case_id";
            
            if ($conn->query($query)) {
                $response['success'] = true;
                $response['message'] = 'Status skrzynki został zmieniony';
            } else {
                throw new Exception('Błąd zmiany statusu: ' . $conn->error);
            }
            break;

        default:
            throw new Exception('Nieznana akcja: ' . $action);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
