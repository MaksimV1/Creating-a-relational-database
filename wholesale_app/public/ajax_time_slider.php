<?php
require 'config.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false];

if ($action === 'get_time') {
    $response['success'] = true;
    $response['virtual_time'] = getVirtualTime($pdo);
    echo json_encode($response);
    exit;
}

if ($action === 'set_time' && isset($_POST['new_time'])) {
    $newTime = $_POST['new_time'];
    try {
        setVirtualTime($pdo, $newTime);
        $response['success'] = true;
        $response['virtual_time'] = getVirtualTime($pdo);
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    echo json_encode($response);
    exit;
}

if ($action === 'reset_time') {
    try {
        $pdo->exec("CALL ResetDatabaseToInitialState()"); // полный сброс БД
        $response['success'] = true;
        $response['virtual_time'] = getVirtualTime($pdo);
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    echo json_encode($response);
    exit;
}

if ($action === 'get_min_max_dates') {
    $stmt = $pdo->query("SELECT MIN(order_date) as min_date, MAX(shipment_date) as max_date FROM `order`");
    $row = $stmt->fetch();
    $response['success'] = true;
    $response['min_date'] = $row['min_date'] ?? date('Y-m-d');
    $response['max_date'] = $row['max_date'] ?? date('Y-m-d', strtotime('+1 year'));
    echo json_encode($response);
    exit;
}
?>