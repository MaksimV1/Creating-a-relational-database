<?php
require 'config.php';
header('Content-Type: application/json');

$warehouse_id = $_POST['warehouse_id'] ?? 0;
$product_id = $_POST['product_id'] ?? 0;
$quantity = (int)($_POST['quantity'] ?? 0);

if (!$warehouse_id || !$product_id || $quantity <= 0) {
    echo json_encode(['error' => 'Неверные параметры']);
    exit;
}

try {
    // Проверяем, существует ли запись о запасе
    $stmt = $pdo->prepare("SELECT id FROM warehouse_stock WHERE warehouse_link = ? AND product_link = ?");
    $stmt->execute([$warehouse_id, $product_id]);
    if ($stmt->fetch()) {
        $update = $pdo->prepare("UPDATE warehouse_stock SET stored = stored + ? WHERE warehouse_link = ? AND product_link = ?");
        $update->execute([$quantity, $warehouse_id, $product_id]);
    } else {
        $insert = $pdo->prepare("INSERT INTO warehouse_stock (warehouse_link, product_link, stored, reserved) VALUES (?, ?, ?, 0)");
        $insert->execute([$warehouse_id, $product_id, $quantity]);
    }
    echo json_encode(['message' => "Склад пополнен на $quantity единиц"]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>