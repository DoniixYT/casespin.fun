<?php
// AJAX endpoint do wyszukiwania itemków w bazie danych
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Debug - dodaj błędy do odpowiedzi
error_reporting(E_ALL);
ini_set('display_errors', 0); // Nie pokazuj błędów w output

if (!isset($conn)) { 
    require_once __DIR__ . '/../config.php'; 
}

// Debug info
$debug_info = [
    'conn_exists' => isset($conn),
    'conn_type' => isset($conn) ? get_class($conn) : 'null',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'get_params' => $_GET
];

$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? max(10, min(500, (int)$_GET['per_page'])) : 100;
$offset = ($page - 1) * $per_page;

$response = [
    'success' => false,
    'items' => [],
    'total' => 0,
    'page' => $page,
    'per_page' => $per_page,
    'total_pages' => 0
];

try {
    if ($conn) {
        if (empty($search_term)) {
            // Bez wyszukiwania - pokaż wszystkie
            $count_query = "SELECT COUNT(*) as total FROM items";
            $items_query = "SELECT * FROM items ORDER BY id ASC LIMIT $per_page OFFSET $offset";
        } else {
            // Z wyszukiwaniem - szukaj w całej bazie
            $search_escaped = $conn->real_escape_string($search_term);
            
            // Sprawdź czy to liczba (dla wyszukiwania po ID)
            $is_numeric = is_numeric($search_term);
            
            $where_conditions = [
                "skin_name LIKE '%$search_escaped%'",
                "weapon_name LIKE '%$search_escaped%'", 
                "item_id LIKE '%$search_escaped%'",
                "rarity LIKE '%$search_escaped%'"
            ];
            
            // Jeśli to liczba, dodaj wyszukiwanie po ID
            if ($is_numeric) {
                $where_conditions[] = "id = " . (int)$search_term;
            }
            
            $where_clause = implode(' OR ', $where_conditions);
            
            $count_query = "SELECT COUNT(*) as total FROM items WHERE $where_clause";
            $items_query = "SELECT * FROM items WHERE $where_clause ORDER BY id ASC LIMIT $per_page OFFSET $offset";
        }
        
        // Policz wyniki
        $count_result = $conn->query($count_query);
        if ($count_result) {
            $response['total'] = (int)$count_result->fetch_assoc()['total'];
            $response['total_pages'] = ceil($response['total'] / $per_page);
        }
        
        // Pobierz wyniki
        $items_result = $conn->query($items_query);
        if ($items_result && $items_result->num_rows > 0) {
            while ($row = $items_result->fetch_assoc()) {
                $response['items'][] = $row;
            }
        }
        
        $response['success'] = true;
        $response['search_term'] = $search_term;
        $response['query_time'] = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $response['debug'] = $debug_info;
        $response['queries'] = [
            'count' => $count_query ?? 'not set',
            'items' => $items_query ?? 'not set'
        ];
    } else {
        $response['error'] = 'Brak połączenia z bazą danych';
        $response['debug'] = $debug_info;
    }
} catch (Exception $e) {
    $response['error'] = 'Błąd wyszukiwania: ' . $e->getMessage();
    $response['debug'] = $debug_info;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
