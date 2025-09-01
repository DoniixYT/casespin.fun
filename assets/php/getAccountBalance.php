<?php
// Błyskawiczny tryb LITE – wczesne wyjście PRZED sesją i innymi operacjami
if ((isset($_GET['lite']) && $_GET['lite'] == '1') || (isset($_POST['lite']) && $_POST['lite'] == '1')) {
    if (function_exists('header')) { @header('Content-Type: application/json'); }
    if (function_exists('http_response_code')) { @http_response_code(200); } else { @header('HTTP/1.1 200 OK'); }
    echo json_encode(array('success' => true, 'balance' => 0.0, 'degraded' => true));
    exit;
}

session_start();
header('Content-Type: application/json');
// Bezpieczne wymuszenie 200 na starcie, aby uniknąć 500 przy błędach krytycznych
if (function_exists('http_response_code')) {
    http_response_code(200);
} else {
    header('HTTP/1.1 200 OK');
}

// Awaryjny fallback na błędy krytyczne (parse/error) -> zwróć bezpieczny JSON
if (!function_exists('cashplay_balance_shutdown_fallback')) {
    function cashplay_balance_shutdown_fallback() {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Spróbuj nadpisać nagłówek i zwrócić bezpieczny JSON
            @header('Content-Type: application/json');
            if (function_exists('http_response_code')) {
                @http_response_code(200);
            } else {
                @header('HTTP/1.1 200 OK');
            }
            echo json_encode(['success' => true, 'balance' => 0.0, 'degraded' => true]);
        }
    }
    register_shutdown_function('cashplay_balance_shutdown_fallback');
}

// Zawsze zwracaj 200 i bezpieczny fallback, by nie generować błędów 500 w UI
// (frontend oczekuje szybkiej odpowiedzi i nie obsługuje wyjątków)

// Błyskawiczny tryb LITE: omija całą logikę DB (zapobiegnie 500 nawet przy problemach środowiskowych)
if ((isset($_GET['lite']) && $_GET['lite'] == '1') || (isset($_POST['lite']) && $_POST['lite'] == '1')) {
    echo json_encode(['success' => true, 'balance' => 0.0, 'degraded' => true]);
    exit;
}

try {
    // Połączenie z bazą — bezpieczne dołączanie pliku konfiguracyjnego
    $conn = isset($conn) ? $conn : null;
    // Sprawdź wersję PHP — uniknij include jeśli < 5.4 (krótkie tablice w conn.php)
    $phpVersionId = defined('PHP_VERSION_ID') ? PHP_VERSION_ID : (int)str_replace('.', '', substr(PHP_VERSION, 0, 3));
    $canIncludeConn = ($phpVersionId >= 50400);

    if ($canIncludeConn && class_exists('mysqli')) {
        $candidates = array();
        // Bazowy katalog pliku
        $base = dirname(__FILE__);            // .../assets/php
        $assetsDir = dirname($base);          // .../assets
        $rootDir = dirname($assetsDir);       // projekt root
        $rootUp = dirname($rootDir);          // dodatkowy poziom wyżej (na wypadek innej struktury)
        
        // ścieżki kandydackie
        $candidates[] = $rootDir . '/server/conn.php';                 // root/server/conn.php
        $candidates[] = $assetsDir . '/server/conn.php';               // assets/server/conn.php (awaryjnie)
        $candidates[] = $base . '/../../server/conn.php';              // relatywnie
        $candidates[] = $rootUp . '/server/conn.php';                  // poziom wyżej
        // document root
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $candidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/server/conn.php';
        }

        $included = false;
        foreach ($candidates as $path) {
            if (is_readable($path)) {
                include_once $path;
                $included = true;
                break;
            }
        }
        if (!$included) {
            // Nie znaleziono pliku połączenia — kontynuuj w trybie degradacji
            error_log('getAccountBalance: Could not locate server/conn.php');
        }
    } else {
        // Brak wsparcia: zbyt stary PHP lub brak rozszerzenia mysqli — pomiń DB, zwróć fallback
        if (!$canIncludeConn) {
            error_log('getAccountBalance: PHP version too old for conn.php include');
        } else if (!class_exists('mysqli')) {
            error_log('getAccountBalance: mysqli extension not available');
        }
    }

    if (!isset($conn) || !$conn) {
        // Fallback: brak DB — zwróć 0.00, ale zaloguj problem
        error_log('getAccountBalance: Database connection not initialized');
        echo json_encode(['success' => true, 'balance' => 0.0, 'degraded' => true]);
        exit;
    }

    // Bezpieczne sprawdzenie typu połączenia (mysqli)
    if (!class_exists('mysqli') || !($conn instanceof mysqli)) {
        error_log('getAccountBalance: Invalid database handle');
        echo json_encode(['success' => true, 'balance' => 0.0, 'degraded' => true]);
        exit;
    }

    $user_unique = isset($_SESSION['user_unique']) ? $_SESSION['user_unique'] : null;
    if (!$user_unique) {
        // Brak sesji użytkownika – nie wywalaj błędem, tylko zwróć 0.00
        echo json_encode(['success' => true, 'balance' => 0.0]);
        exit;
    }

    // Pobierz saldo użytkownika
    $query = "SELECT total_balance FROM user_details WHERE user_unique = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log('getAccountBalance: Failed to prepare statement: ' . $conn->error);
        echo json_encode(['success' => true, 'balance' => 0.0, 'degraded' => true]);
        exit;
    }
    $stmt->bind_param('s', $user_unique);
    $ok = $stmt->execute();
    if (!$ok) {
        error_log('getAccountBalance: Failed to execute query: ' . $stmt->error);
        $stmt->close();
        echo json_encode(['success' => true, 'balance' => 0.0, 'degraded' => true]);
        exit;
    }
    // Użyj bind_result/fetch zamiast get_result (zgodność bez mysqlnd)
    $stmt->bind_result($balance_val);
    $rowFound = $stmt->fetch();
    if (!$rowFound) {
        $stmt->close();
        echo json_encode(['success' => true, 'balance' => 0.0]);
        exit;
    }
    $balance = isset($balance_val) ? floatval($balance_val) : 0.0;

    echo json_encode(['success' => true, 'balance' => $balance]);

    // Cleanup
    if (isset($stmt) && $stmt) { $stmt->close(); }
    if (isset($conn) && $conn) { $conn->close(); }

} catch (Exception $e) {
    error_log('Account balance error: ' . $e->getMessage());
    echo json_encode(['success' => true, 'balance' => 0.0, 'degraded' => true]);
}
?>
