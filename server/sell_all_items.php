<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache');

ob_start();
include 'conn.php';
ob_end_clean();

// Check if user is logged in
if (!isset($_SESSION['user_unique']) || empty($_SESSION['user_unique'])) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    ob_end_flush();
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$user_unique = $_SESSION['user_unique'];

// Verify user_unique matches session
if (!isset($input['user_unique']) || $input['user_unique'] !== $user_unique) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    ob_end_flush();
    exit();
}

// Check database connection
if (!$conn) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    ob_end_flush();
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get all user's current inventory items (fresh query to avoid stale data)
    $inventory_query = $conn->prepare("
        SELECT ui.*, ci.name as item_name, ci.rarity as item_rarity
        FROM user_inventory ui
        LEFT JOIN case_items ci ON ui.item_id = ci.id
        WHERE ui.user_unique = ?
    ");
    $inventory_query->bind_param("s", $user_unique);
    $inventory_query->execute();
    $inventory_result = $inventory_query->get_result();
    
    if ($inventory_result->num_rows === 0) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'No items to sell']);
        exit();
    }
    
    $total_value = 0;
    $items_sold = 0;
    $items_data = [];
    
    // Collect all items data and calculate total value
    while ($item = $inventory_result->fetch_assoc()) {
        $quantity = $item['quantity'] ?? 1;
        $item_value = $item['item_value'] * $quantity;
        $total_value += $item_value;
        $items_sold += $quantity;
        
        // Store item data for individual sale records
        $items_data[] = [
            'id' => $item['id'],
            'item_id' => $item['item_id'],
            'item_name' => $item['item_name'],
            'item_rarity' => $item['item_rarity'],
            'item_value' => $item['item_value'],
            'quantity' => $quantity
        ];
    }
    
    // Insert individual sale records into item_sales table (optional) jeśli tabela istnieje
    $table_check = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'item_sales'");
    if ($table_check && $table_check->fetch_row()) {
        $sale_query = $conn->prepare("
            INSERT INTO item_sales (user_unique, item_id, item_name, sale_value, sold_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        if ($sale_query) {
            foreach ($items_data as $item) {
                // Calculate total value for this item (price * quantity)
                $total_item_value = $item["item_value"] * $item["quantity"];
                $sale_query->bind_param(
                    "sisd",
                    $user_unique,
                    $item["item_id"],
                    $item["item_name"],
                    $total_item_value
                );
                if (!$sale_query->execute()) {
                    $conn->rollback();
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Failed to record sales']);
                    ob_end_flush();
                    exit();
                }
            }
        }
    }
    
    // Delete all inventory items
    $delete_query = $conn->prepare("DELETE FROM user_inventory WHERE user_unique = ?");
    $delete_query->bind_param("s", $user_unique);
    
    if (!$delete_query->execute()) {
        $conn->rollback();
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to sell items']);
        ob_end_flush();
        exit();
    }
    
    // Add money to user's balance
    $update_balance_query = $conn->prepare("
        UPDATE user_details 
        SET total_balance = total_balance + ? 
        WHERE user_unique = ?
    ");
    $update_balance_query->bind_param("ds", $total_value, $user_unique);
    
    if (!$update_balance_query->execute()) {
        $conn->rollback();
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to update balance']);
        ob_end_flush();
        exit();
    }
    
    // Commit transaction
    $conn->commit();
    
    ob_clean();
    echo json_encode([
        'success' => true, 
        'message' => 'All items sold successfully',
        'total_value' => $total_value,
        'items_sold' => $items_sold
    ]);
    ob_end_flush();
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Sell all items error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
    ob_end_flush();
    exit();
}
?>
