<?php
require 'config.php';
header('Content-Type: application/json');

$procedure = $_POST['procedure'] ?? '';
$params = $_POST['params'] ?? [];

try {
    switch ($procedure) {
        case 'UpsertClient':
            $id = !empty($params['id']) ? $params['id'] : null;
            $stmt = $pdo->prepare("CALL UpsertClient(?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $params['name'] ?? '',
                $params['phone'] ?? '',
                $params['balance'] ?? 0,
                $params['region_id'] ?? 1,
                $params['address_id'] ?? 1
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($result ?: ['message' => 'Выполнено']);
            break;
            
        case 'ОтчётПоКлиенту':
            $stmt = $pdo->prepare("CALL ОтчётПоКлиенту(?)");
            $stmt->execute([$params['client_id'] ?? 0]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($result);
            break;
            
        case 'ProcessBackorders':
            $stmt = $pdo->prepare("CALL ProcessBackorders(?)");
            $stmt->execute([$params['product_id'] ?? 0]);
            echo json_encode(['message' => 'Бэк-заказы обработаны']);
            break;
            
        case 'GetClientOrders':
            $stmt = $pdo->prepare("CALL GetClientOrders(?)");
            $stmt->execute([$params['client_id'] ?? 0]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['error' => 'Неизвестная процедура: ' . htmlspecialchars($procedure)]);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>