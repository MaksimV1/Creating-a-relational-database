<?php
require 'config.php';
$table = $_GET['table'] ?? '';
if (!$table) {
    die("Таблица не указана");
}
$current_vt = getVirtualTime($pdo);
$additionalWhere = '';
if ($table === 'order') {
    // Показываем заказы, которые ещё не должны быть полностью отгружены (shipment_date > current_vt или shipment_date NULL или есть неотгруженные позиции)
    $additionalWhere = "WHERE (shipment_date IS NULL OR shipment_date > '$current_vt') 
                        AND EXISTS (SELECT 1 FROM order_item oi WHERE oi.order_link = id AND oi.shipped < oi.quantity)";
} elseif ($table === 'order_item') {
    // Для позиций заказа: показываем только те, которые относятся к ещё не отгруженным заказам
    $additionalWhere = "WHERE order_link IN (SELECT id FROM `order` WHERE (shipment_date IS NULL OR shipment_date > '$current_vt') 
                                            AND EXISTS (SELECT 1 FROM order_item oi2 WHERE oi2.order_link = id AND oi2.shipped < oi2.quantity))";
}
// Получение структуры таблицы для заголовков
$stmt = $pdo->query("DESCRIBE `$table`");
$columns = $stmt->fetchAll();
$colNames = array_column($columns, 'Field');

// Получение данных
$dataStmt = $pdo->query("SELECT * FROM `$table` $additionalWhere LIMIT 100");
$rows = $dataStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Таблица <?= htmlspecialchars($table) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Таблица: <?= htmlspecialchars($table) ?></h2>
    <a href="index.php" class="btn btn-secondary mb-3">← На главную</a>
    <a href="edit_record.php?table=<?= urlencode($table) ?>" class="btn btn-success mb-3">➕ Добавить запись</a>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <?php foreach ($colNames as $col): ?>
                        <th><?= htmlspecialchars($col) ?></th>
                    <?php endforeach; ?>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($colNames as $col): ?>
                        <td><?= htmlspecialchars(substr($row[$col] ?? '', 0, 100)) ?></td>
                    <?php endforeach; ?>
                    <td>
                        <a href="edit_record.php?table=<?= urlencode($table) ?>&id=<?= urlencode($row[key($row)]) ?>" class="btn btn-sm btn-primary">✏️</a>
                        <a href="delete_record.php?table=<?= urlencode($table) ?>&id=<?= urlencode($row[key($row)]) ?>&pk=<?= urlencode(key($row)) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить запись?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>