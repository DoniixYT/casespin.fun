<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// Fallback data
$fallback_drops = [
    [
        'id' => 1,
        'username' => 'Player1',
        'item_name' => 'AK-47 | Redline',
        'item_value' => 25.50,
        'rarity' => 'classified',
        'case_name' => 'Chroma Case',
        'timestamp' => time() - 120
    ],
    [
        'id' => 2,
        'username' => 'Player2', 
        'item_name' => 'AWP | Dragon Lore',
        'item_value' => 1250.00,
        'rarity' => 'covert',
        'case_name' => 'Cobblestone Case',
        'timestamp' => time() - 300
    ],
    [
        'id' => 3,
        'username' => 'Player3',
        'item_name' => 'Glock-18 | Water Elemental',
        'item_value' => 8.75,
        'rarity' => 'restricted',
        'case_name' => 'Chroma Case',
        'timestamp' => time() - 480
    ]
];

try {
    $drops = $fallback_drops;
    
    // Try to get real data if possible
    ob_start();
    require_once 'conn.php';
    ob_end_clean();
    
    if (isset($conn) && $conn) {
        $case_id = isset($_GET['case_id']) ? intval($_GET['case_id']) : 1;
        
        $query = "SELECT id, name, value, rarity FROM case_items WHERE case_id = ? ORDER BY value DESC";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param('i', $case_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $drops = [];
                while ($row = $result->fetch_assoc()) {
                    $drops[] = [
                        'id' => intval($row['id']),
                        'name' => $row['name'],
                        'value' => floatval($row['value']),
                        'rarity' => $row['rarity']
                    ];
                }
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
