<?php
$host = 'MySQL-8.0';
$dbname = 'wholesale_company';
$username = 'root';
$password = '';
$port = 3306;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

function getVirtualTime($pdo) {
    $stmt = $pdo->query("SELECT GetVirtualTime() as vt");
    $row = $stmt->fetch();
    return $row['vt'];
}

function setVirtualTime($pdo, $newTime) {
    $stmt = $pdo->prepare("CALL SetVirtualTime(?)");
    $stmt->execute([$newTime]);
}
?>