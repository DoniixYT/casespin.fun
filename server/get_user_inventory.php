<?php
session_start();
header('Content-Type: application/json');

// Force no cache
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Pragma: no-cache');

include 'conn.php';

// Debug: Check session
error_log('Inventory: Session data: ' . print_r($_SESSION, true));

// Check if user is logged in
if (!isset($_SESSION['user_unique']) || empty($_SESSION['user_unique'])) {
    error_log('Inventory: User not logged in');
    echo json_encode(['error' => 'Not logged in', 'session_debug' => $_SESSION]);
    exit();
}

error_log('Inventory: User logged in: ' . $_SESSION['user_unique']);

$user_unique = $_SESSION['user_unique'];

// Get user's inventory with item details (prepared statement, no LIMIT)
$stmt = $conn->prepare("
    SELECT
        ui.id,
        ui.item_id,
        ui.item_name,
        ui.item_value,
        ui.item_rarity,
        ui.quantity,
        ui.acquired_at,
        ci.image,
        ci.description
    FROM user_inventory ui
    LEFT JOIN case_items ci ON ui.item_id = ci.id
    WHERE ui.user_unique = ?
    ORDER BY ui.item_value DESC, ui.acquired_at DESC
");
if (!$stmt) {
    echo json_encode(['items' => [], 'total_value' => 0, 'item_count' => 0, 'message' => 'Query prepare failed']);
    exit();
}
$stmt->bind_param('s', $user_unique);
$stmt->execute();
$inventory_result = $stmt->get_result();

$inventory_items = [];
$total_value = 0;

while ($item = $inventory_result->fetch_assoc()) {
    $quantity = $item['quantity'] ?? 1;
    $item_value = floatval($item['item_value']);
    $total_value += $item_value * $quantity;
    
    $inventory_items[] = [
        // Return both the inventory row id and original item_id for compatibility
        'id' => intval($item['id']),            // user_inventory row id
        'item_id' => $item['item_id'],          // case_items id
        'name' => $item['item_name'],
        'value' => $item['item_value'],
        'rarity' => $item['item_rarity'],
        'quantity' => $quantity,
        'image' => $item['image'],
        'description' => $item['description'],
        'acquired_at' => $item['acquired_at']
    ];
}

echo json_encode([
    'items' => $inventory_items,
    'total_value' => $total_value,
    'item_count' => count($inventory_items)
]);
?>
