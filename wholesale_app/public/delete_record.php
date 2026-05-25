<?php
require 'config.php';
$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? '';
$pk = $_GET['pk'] ?? 'id';
if ($table && $id) {
    $sql = "DELETE FROM `$table` WHERE `$pk` = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    header("Location: view_table.php?table=" . urlencode($table));
} else {
    die("Недостаточно параметров");
}
?>