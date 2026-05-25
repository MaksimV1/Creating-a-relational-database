<?php
require 'config.php';
$password = $_POST['password'] ?? '';
$sql = $_POST['sql'] ?? '';

if ($password !== 'root') {
    die("<div class='alert alert-danger'>Неверный пароль</div>");
}
if (empty($sql)) {
    die("<div class='alert alert-warning'>Введите SQL-запрос</div>");
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    if (stripos($sql, 'select') === 0) {
        $rows = $stmt->fetchAll();
        if (empty($rows)) {
            echo "<div class='alert alert-info'>Нет результатов</div>";
        } else {
            echo "<div class='table-responsive'><table class='table table-bordered table-sm'>";
            echo "<thead><tr>";
            foreach (array_keys($rows[0]) as $col) echo "<th>" . htmlspecialchars($col) . "</th>";
            echo "</tr></thead><tbody>";
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $val) echo "<td>" . htmlspecialchars((string)$val) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table></div>";
        }
    } else {
        $count = $stmt->rowCount();
        echo "<div class='alert alert-success'>Запрос выполнен. Затронуто строк: $count</div>";
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>