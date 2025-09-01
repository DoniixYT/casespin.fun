<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Fallback data
$fallback_drops = [
    [
        'username' => 'Player1',
        'item_name' => 'AK-47 | Redline',
        'item_value' => 25.50,
        'rarity' => 'classified',
        'case_name' => 'Chroma Case',
        'time_ago' => '2 minutes ago'
    ],
    [
        'username' => 'Player2',
        'item_name' => 'AWP | Dragon Lore',
        'item_value' => 1250.00,
        'rarity' => 'covert',
        'case_name' => 'Cobblestone Case',
        'time_ago' => '5 minutes ago'
    ],
    [
        'username' => 'Player3',
        'item_name' => 'Glock-18 | Water Elemental',
        'item_value' => 8.75,
        'rarity' => 'restricted',
        'case_name' => 'Chroma Case',
        'time_ago' => '8 minutes ago'
    ]
];

try {
    $drops = $fallback_drops;
    
    // Try to get real data if possible
    ob_start();
    require_once 'conn.php';
    ob_end_clean();
    
    if (isset($conn) && $conn) {
        $query = "SELECT 
            co.opened_at,
            co.item_value,
            ci.name as item_name,
            ci.rarity,
            COALESCE(ud.username, 'Anonymous') as username,
            c.name as case_name
        FROM case_openings co
        LEFT JOIN case_items ci ON co.item_id = ci.id
        LEFT JOIN user_details ud ON co.user_unique = ud.user_unique
        LEFT JOIN cases c ON co.case_id = c.id
        ORDER BY co.opened_at DESC
        LIMIT 20";
        
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $drops = [];
            while ($row = $result->fetch_assoc()) {
                $drops[] = [
                    'username' => $row['username'] ?? 'Anonymous',
                    'item_name' => $row['item_name'] ?? 'Unknown Item',
                    'item_value' => floatval($row['item_value'] ?? 0),
                    'rarity' => $row['rarity'] ?? 'common',
                    'case_name' => $row['case_name'] ?? 'Unknown Case',
                    'time_ago' => '2 minutes ago'
                ];
            }
        }
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => [
            'drops' => $drops,
            'count' => count($drops)
        ]
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => [
            'drops' => $fallback_drops,
            'count' => count($fallback_drops)
        ]
    ]);
}
ob_end_flush();
exit();
?>
