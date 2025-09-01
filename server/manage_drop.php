<?php
// manage_drop.php
// Dodawanie, edytowanie i usuwanie dropów do skrzynek (case_items)

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

require_once 'conn.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean(); echo json_encode(['success' => false, 'message' => 'Nieprawidłowa metoda żądania']); ob_end_flush();
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $case_id = intval($_POST['case_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $value = floatval($_POST['value'] ?? 0);
    $rarity = trim($_POST['rarity'] ?? '');
    $chance = floatval($_POST['chance'] ?? 0);
    $image = trim($_POST['image'] ?? '');

    if (!$case_id || !$name || !$rarity || !$chance) {
        ob_clean(); echo json_encode(['success' => false, 'message' => 'Wszystkie wymagane pola muszą być uzupełnione']); ob_end_flush();
        exit;
    }
    if ($value < 0) $value = 0;
    if ($chance <= 0 || $chance > 100) {
        ob_clean(); echo json_encode(['success' => false, 'message' => 'Szansa musi być z zakresu 0-100']); ob_end_flush();
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO case_items (case_id, name, value, rarity, chance, image) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        ob_clean(); echo json_encode(['success' => false, 'message' => 'Błąd bazy danych']); ob_end_flush();
        exit;
    }
    $stmt->bind_param('isdsss', $case_id, $name, $value, $rarity, $chance, $image);
    $ok = $stmt->execute();
    if ($ok) {
        ob_clean(); echo json_encode(['success' => true, 'message' => 'Drop dodany pomyślnie']); ob_end_flush();
    } else {
        ob_clean(); echo json_encode(['success' => false, 'message' => 'Nie udało się dodać dropa']); ob_end_flush();
    }
    exit;
}

if ($action === 'delete') {
    $drop_id = intval($_POST['drop_id'] ?? 0);
    if (!$drop_id) {
        ob_clean(); echo json_encode(['success' => false, 'message' => 'Brak ID dropa']); ob_end_flush();
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM case_items WHERE id = ?");
    if (!$stmt) {
        ob_clean(); echo json_encode(['success' => false, 'message' => 'Błąd bazy danych']); ob_end_flush();
        exit;
    }
    $stmt->bind_param('i', $drop_id);
    $ok = $stmt->execute();
    if ($ok) {
        ob_clean(); echo json_encode(['success' => true, 'message' => 'Drop usunięty']); ob_end_flush();
    } else {
        ob_clean(); echo json_encode(['success' => false, 'message' => 'Nie udało się usunąć dropa']); ob_end_flush();
    }
    exit;
}

if ($action === 'edit') {
    $drop_id = intval($_POST['drop_id'] ?? 0);
    $case_id = intval($_POST['case_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $value = floatval($_POST['value'] ?? 0);
    $rarity = trim($_POST['rarity'] ?? 'common');
    $chance = floatval($_POST['chance'] ?? 0);
    $image = trim($_POST['image'] ?? '');

    if (!$drop_id || !$case_id || $name === '' || $value < 0 || $chance <= 0 || $chance > 100) {
        ob_clean(); echo json_encode(['success' => false, 'message' => 'Nieprawidłowe dane edycji dropa']); ob_end_flush();
        exit;
    }

    $stmt = $conn->prepare("UPDATE case_items SET name = ?, value = ?, rarity = ?, chance = ?, image = ? WHERE id = ? AND case_id = ?");
    if (!$stmt) {
        ob_clean(); echo json_encode(['success' => false, 'message' => 'Błąd bazy danych']); ob_end_flush();
        exit;
    }
    $stmt->bind_param('sdsssii', $name, $value, $rarity, $chance, $image, $drop_id, $case_id);
    $ok = $stmt->execute();
    if ($ok) {
        ob_clean(); echo json_encode(['success' => true, 'message' => 'Drop zaktualizowany']); ob_end_flush();
    } else {
        ob_clean(); echo json_encode(['success' => false, 'message' => 'Nie udało się zaktualizować dropa']); ob_end_flush();
    }
    exit;
}

ob_clean(); echo json_encode(['success' => false, 'message' => 'Nieznana akcja']); ob_end_flush();
