<?php


ob_start();


if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}


$conn_path = __DIR__ . '/../server/conn.php';
if (file_exists($conn_path)) {
    include $conn_path;
} else {
    die('Database connection file not found');
}


function isAdmin() {
    global $conn;

    $currentUser = getCurrentUserUnique();
    if (!$currentUser) {
        error_log('[ADMIN] Access denied: missing user_unique in session/cookie');
        return false;
    }

    
    if (!isset($conn) || !$conn) {
        return false;
    }

    try {
        
        $sql = "SELECT admin_access FROM user_details WHERE user_unique = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $currentUser);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;

            if ($row && isset($row['admin_access']) && (string)$row['admin_access'] === '1') {
                return true;
            }
        }

        
        if (!empty($_SESSION['username'])) {
            $u = $_SESSION['username'];
            $q = $conn->prepare("SELECT admin_access FROM user_details WHERE username = ? LIMIT 1");
            if ($q) {
                $q->bind_param('s', $u);
                $q->execute();
                $r = $q->get_result();
                $w = $r ? $r->fetch_assoc() : null;
                if ($w && isset($w['admin_access']) && (string)$w['admin_access'] === '1') { return true; }
            }
        }

        
        $steamSessionKeys = ['steamid', 'steam_id', 'steamid64'];
        $steamColumnCandidates = ['steamid', 'steam_id', 'steamid64'];
        foreach ($steamSessionKeys as $ssk) {
            if (!empty($_SESSION[$ssk])) {
                $sid = $_SESSION[$ssk];
                foreach ($steamColumnCandidates as $col) {
                    $q = $conn->prepare("SELECT admin_access FROM user_details WHERE $col = ? LIMIT 1");
                    if ($q) {
                        $q->bind_param('s', $sid);
                        $q->execute();
                        $r = $q->get_result();
                        $w = $r ? $r->fetch_assoc() : null;
                        if ($w && isset($w['admin_access']) && (string)$w['admin_access'] === '1') { return true; }
                    }
                }
            }
        }

        

        return false;
    } catch (Throwable $e) {
        
        error_log('[ADMIN] Access denied: exception in isAdmin() - ' . $e->getMessage());
        return false;
    }
}


function checkAdminAuth() {
    return isAdmin();
}


function getCurrentUserUnique() {
    
    if (!empty($_SESSION['user_unique'])) {
        return (string)$_SESSION['user_unique'];
    }
    if (!empty($_COOKIE['user_unique'])) {
        return (string)$_COOKIE['user_unique'];
    }
    return null;
}


function getDashboardStats() {
    global $conn;
    
    
    if (!isset($conn) || !$conn) {
        return [
            'total_users' => 0,
            'total_balance' => 0,
            'cases_today' => 0,
            'total_items' => 0
        ];
    }
    
    try {
        
        $users_query = $conn->query("SELECT COUNT(*) as total FROM user_details");
        $total_users = $users_query ? $users_query->fetch_assoc()['total'] : 0;
        
        
        $balance_query = $conn->query("SELECT SUM(total_balance) as total FROM user_details");
        $total_balance = $balance_query ? ($balance_query->fetch_assoc()['total'] ?? 0) : 0;
        
        
        $cases_query = $conn->query("SELECT COUNT(*) as total FROM case_openings WHERE DATE(opened_at) = CURDATE()");
        $cases_today = $cases_query ? $cases_query->fetch_assoc()['total'] : 0;
        
        
        $inventory_query = $conn->query("SELECT COUNT(*) as total FROM user_inventory");
        $total_items = $inventory_query ? $inventory_query->fetch_assoc()['total'] : 0;
        
        return [
            'total_users' => $total_users,
            'total_balance' => $total_balance,
            'cases_today' => $cases_today,
            'total_items' => $total_items
        ];
        
    } catch (Exception $e) {
        return [
            'total_users' => 0,
            'total_balance' => 0,
            'cases_today' => 0,
            'total_items' => 0
        ];
    }
}


function getRecentActivities($limit = 10) {
    global $conn;
    
    
    if (!isset($conn) || !$conn) {
        return [];
    }
    
    try {
        $query = "SELECT co.*, ud.username, c.name as case_name 
                  FROM case_openings co 
                  LEFT JOIN user_details ud ON co.user_unique = ud.user_unique 
                  LEFT JOIN cases c ON co.case_id = c.id 
                  ORDER BY co.opened_at DESC 
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        
        return $activities;
        
    } catch (Exception $e) {
        return [];
    }
}
?>
