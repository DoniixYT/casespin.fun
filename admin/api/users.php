<?php
session_start();
header('Content-Type: application/json');


if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include '../../server/conn.php';
include '../../server/db_query.php';

$action = $_GET['action'] ?? '';

if ($action === 'get_users') {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;

    $sql = "SELECT user_unique, username, email, total_balance, created_at, last_login, status FROM user_details WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (username LIKE ? OR email LIKE ? OR user_unique LIKE ?)";
        $searchTerm = '%' . $search . '%';
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
    }

    if ($status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $countSql = str_replace("SELECT user_unique, username, email, total_balance, created_at, last_login, status FROM user_details", "SELECT COUNT(*) as total FROM user_details", $sql);
    
    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    array_push($params, $limit, $offset);

    try {
        $stmt = db_query($sql, $params);
        $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $stmt = db_query($countSql, array_slice($params, 0, count($params) - 2)); 
        $total_users = $stmt->get_result()->fetch_assoc()['total'];

        echo json_encode(['users' => $users, 'total' => $total_users, 'limit' => $limit]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid action']);
}
?>
