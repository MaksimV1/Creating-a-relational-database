<?php
require 'config.php';

$view = $_GET['view'] ?? '';
if (!$view) {
    die("Представление не указано");
}

// Проверка, что это действительно представление
$stmt = $pdo->prepare("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_wholesale_company = ?");
$stmt->execute([$view]);
if ($stmt->rowCount() == 0) {
    die("Некорректное представление");
}

// Получаем данные ТОЛЬКО ассоциативно
$data = $pdo->query("SELECT * FROM `$view` LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);

if (empty($data)) {
    echo "<div class='alert alert-info'>Нет данных в представлении</div>";
    exit;
}

// Получаем названия столбцов
$columns = array_keys($data[0]);

echo "<div class='table-responsive'>";
echo "<table class='table table-bordered table-striped'>";
// Заголовок
echo "<thead><tr>";
foreach ($columns as $col) {
    echo "<th>" . htmlspecialchars($col) . "</th>";
}
echo "</tr></thead><tbody>";

// Данные
foreach ($data as $row) {
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>" . htmlspecialchars((string)$value) . "</td>";
    }
    echo "</tr>";
}
echo "</tbody></table></div>";
?>