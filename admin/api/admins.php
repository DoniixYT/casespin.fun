<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';

session_start();
header('Content-Type: application/json');


if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$admin_role = $_SESSION['admin_role'] ?? '';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_admins':
            $stmt = db_query("SELECT id, username, email, role, status, created_at, last_login FROM admins ORDER BY created_at DESC");
            $result = $stmt->get_result();
            $admins = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode($admins);
            break;

        case 'add_admin':
            if ($admin_role !== 'Super Admin') {
                throw new Exception('Forbidden: You do not have permission to perform this action.');
            }
            
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'Support';

            if (empty($username) || empty($email) || empty($password) || empty($role)) {
                throw new Exception('All fields are required.');
            }

            if (!in_array($role, ['Super Admin', 'Moderator', 'Support'])) {
                throw new Exception('Invalid role selected.');
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            db_query("INSERT INTO admins (username, email, password_hash, role) VALUES (?, ?, ?, ?)", [$username, $email, $password_hash, $role]);
            
            echo json_encode(['success' => true, 'message' => 'Admin added successfully.']);
            break;

        case 'delete_admin':
             if ($admin_role !== 'Super Admin') {
                throw new Exception('Forbidden: You do not have permission to perform this action.');
            }

            $id = $_POST['id'] ?? 0;
            if ($id == $_SESSION['admin_id']) {
                 throw new Exception('You cannot delete your own account.');
            }

            if ($id > 0) {
                db_query("DELETE FROM admins WHERE id = ?", [$id]);
                echo json_encode(['success' => true, 'message' => 'Admin deleted successfully.']);
            } else {
                throw new Exception('Invalid admin ID.');
            }
            break;

        default:
            throw new Exception('Invalid action specified.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
