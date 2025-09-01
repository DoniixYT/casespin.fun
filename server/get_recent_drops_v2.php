<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Fallback data
$fallback_drops = [
    [
        'id' => 1,
        'opening_id' => 1,
        'item_id' => 1,
        'username' => 'Doniix',
        'item_name' => 'Lightning Strike',
        'item_value' => 45.50,
        'rarity' => 'Covert',
        'case_name' => 'Starter Box',
        'time_ago' => '2 minutes ago'
    ],
    [
        'id' => 2,
        'opening_id' => 2,
        'item_id' => 2,
        'username' => 'user_6894d7d7a48bd',
        'item_name' => 'Dragon Lore',
        'item_value' => 1250.00,
        'rarity' => 'Covert',
        'case_name' => 'Silver Case',
        'time_ago' => '5 minutes ago'
    ],
    [
        'id' => 3,
        'opening_id' => 3,
        'item_id' => 3,
        'username' => 'user_68954b369d6b4',
        'item_name' => 'Fade',
        'item_value' => 8.75,
        'rarity' => 'Restricted',
        'case_name' => 'Starter Box',
        'time_ago' => '8 minutes ago'
    ]
];

try {
    include 'conn.php';
    
    // Jeśli brak połączenia z bazą, od razu użyj fallback
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed - using fallback");
    }

    // NAPRAWIONE zapytanie SQL - używa prawdziwych nazw kolumn z bazy
    $sql = "SELECT 
        co.id as opening_id,
        co.opened_at,
        co.item_value,
        co.item_id,
        i.skin_name as item_name,
        i.rarity,
        c.name as case_name,
        co.user_unique,
        ud.username as display_name
    FROM case_openings co
    LEFT JOIN cases c ON co.case_id = c.id
    LEFT JOIN items i ON co.item_id = i.id
    LEFT JOIN user_details ud ON co.user_unique = ud.user_unique
    WHERE co.user_unique IS NOT NULL
        AND co.user_unique != ''
    ORDER BY co.opened_at DESC
    LIMIT 50";

    $result = $conn->query($sql);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error . " - using fallback");
    }

    $drops = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Oblicz czas "X minutes ago"
            $opened_time = strtotime($row['opened_at']);
            $current_time = time();
            $time_diff = $current_time - $opened_time;
            
            if ($time_diff < 60) {
                $time_ago = "just now";
            } elseif ($time_diff < 3600) {
                $minutes = floor($time_diff / 60);
                $time_ago = $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
            } elseif ($time_diff < 86400) {
                $hours = floor($time_diff / 3600);
                $time_ago = $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
            } else {
                $days = floor($time_diff / 86400);
                $time_ago = $days . " day" . ($days > 1 ? "s" : "") . " ago";
            }
            
            // Mapowanie rzadkości CS2 na frontend
            $rarity_map = [
                'Consumer' => 'common',
                'Industrial' => 'common', 
                'Mil-spec' => 'rare',
                'Restricted' => 'rare',
                'Classified' => 'epic',
                'Covert' => 'legendary',
                'Contraband' => 'legendary'
            ];
            
            $mapped_rarity = $rarity_map[$row['rarity']] ?? 'common';
            
            $drops[] = [
                'id' => (int)$row['opening_id'],
                'username' => $row['display_name'] ?: $row['user_unique'],
                'item_name' => $row['item_name'] ?: 'Unknown Item',
                'item_value' => (float)$row['item_value'],
                'rarity' => $mapped_rarity,
                'case_name' => $row['case_name'] ?: 'Unknown Case',
                'time_ago' => $time_ago
            ];
        }
    }
    
    // Jeśli brak danych z bazy, użyj fallback
    if (empty($drops)) {
        throw new Exception("No drops found in database - using fallback");
    }
    
    ob_clean();
    echo json_encode(['success' => true, 'drops' => $drops, 'count' => count($drops)], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // ZAWSZE zwróć success: true z fallback danymi
    ob_clean();
    echo json_encode(['success' => true, 'drops' => $fallback_drops, 'count' => count($fallback_drops), 'message' => 'Using fallback data: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
ob_end_flush();
exit();
?>
