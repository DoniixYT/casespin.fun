<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

http_response_code(200);
header('Content-Type: application/json');

try {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    
    @include_once '../config.php';
    if (!function_exists('isAdmin') || !isAdmin()) {
        echo json_encode(['status' => 'success', 'data' => [
            'total_users' => 0,
            'total_balance' => 0,
            'cases_today' => 0,
            'total_items' => 0
        ]]);
        exit;
    }

    @include_once '../../server/conn.php';

    $action = $_GET['action'] ?? '';

    if ($action === 'get_stats') {
        if (!isset($conn) || !$conn) {
            echo json_encode(['status' => 'success', 'data' => [
                'total_users' => 0,
                'total_balance' => 0,
                'cases_today' => 0,
                'total_items' => 0
            ]]);
            exit;
        }

        $total_users = 0; $total_balance = 0; $cases_today = 0; $total_items = 0;

        if ($res = $conn->query("SELECT COUNT(*) AS total FROM user_details")) {
            $row = $res->fetch_assoc();
            $total_users = (int)($row['total'] ?? 0);
        }

        if ($res = $conn->query("SELECT SUM(total_balance) AS total FROM user_details")) {
            $row = $res->fetch_assoc();
            $total_balance = (float)($row['total'] ?? 0);
        }

        if ($res = $conn->query("SELECT COUNT(*) AS total FROM case_openings WHERE DATE(opened_at) = CURDATE()")) {
            $row = $res->fetch_assoc();
            $cases_today = (int)($row['total'] ?? 0);
        }

        
        if ($res = $conn->query("SELECT SUM(quantity) AS total FROM user_inventory")) {
            $row = $res->fetch_assoc();
            $total_items = (int)($row['total'] ?? 0);
        }

        $stats = [
            'total_users' => $total_users,
            'total_balance' => $total_balance,
            'cases_today' => $cases_today,
            'total_items' => $total_items
        ];

        echo json_encode(['status' => 'success', 'data' => $stats]);
        exit;
    }

    echo json_encode(['status' => 'success', 'data' => [
        'total_users' => 0,
        'total_balance' => 0,
        'cases_today' => 0,
        'total_items' => 0
    ]]);
    exit;

} catch (Throwable $e) {
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => [
        'total_users' => 0,
        'total_balance' => 0,
        'cases_today' => 0,
        'total_items' => 0
    ]]);
    exit;
}
?>
