<?php
require 'config.php';

$views = [
    'TopClientsByOrderSum' => 'Топ-5 клиентов по сумме заказов',
    'ProductsBelowMinLevel' => 'Товары ниже минимального уровня',
    'EmployeeOrderLoad' => 'Загруженность сотрудников',
    'BackorderSummary' => 'Отложенные заказы'
];

$selected = $_GET['view'] ?? 'TopClientsByOrderSum';
if (!array_key_exists($selected, $views)) {
    $selected = 'TopClientsByOrderSum';
}

$data = [];
try {
    if ($selected === 'TopClientsByOrderSum') {
        $vt = getVirtualTime($pdo);
        $sql = "SELECT c.id, c.name, SUM(oi.shipped * p.price) as total_spent
                FROM client c
                JOIN `order` o ON c.id = o.client_link
                JOIN order_item oi ON o.id = oi.order_link
                JOIN product p ON oi.product_id = p.id
                WHERE o.shipment_date IS NOT NULL AND o.shipment_date <= '$vt'
                GROUP BY c.id
                ORDER BY total_spent DESC LIMIT 5";
        $data = $pdo->query($sql)->fetchAll();
    } elseif ($selected === 'ProductsBelowMinLevel') {
        $sql = "SELECT p.id, p.name, p.min_level, COALESCE(SUM(ws.stored), 0) as total_stored
                FROM product p
                LEFT JOIN warehouse_stock ws ON p.id = ws.product_link
                GROUP BY p.id
                HAVING total_stored < p.min_level";
        $data = $pdo->query($sql)->fetchAll();
    } elseif ($selected === 'EmployeeOrderLoad') {
        $sql = "SELECT e.id, e.surname, e.name, COUNT(o.id) as orders_handled
                FROM employee e
                LEFT JOIN `order` o ON e.id = o.employee_id
                GROUP BY e.id";
        $data = $pdo->query($sql)->fetchAll();
    } elseif ($selected === 'BackorderSummary') {
        $sql = "SELECT bo.order_link, o.client_link, c.name as client_name, p.name as product_name, bo.quantity, bo.created_at
                FROM backorder_item bo
                JOIN `order` o ON bo.order_link = o.id
                JOIN client c ON o.client_link = c.id
                JOIN product p ON bo.product_id = p.id
                ORDER BY bo.created_at";
        $data = $pdo->query($sql)->fetchAll();
    }
} catch (PDOException $e) {
    $data = [];
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Отчёты</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Аналитические отчёты</h2>
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <?php foreach ($views as $key => $title): ?>
                    <a href="?view=<?= urlencode($key) ?>" class="list-group-item list-group-item-action <?= $selected == $key ? 'active' : '' ?>"><?= htmlspecialchars($title) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-9">
            <h3><?= htmlspecialchars($views[$selected]) ?></h3>
            <?php if (!empty($data)): ?>
                <table class="table table-bordered">
                    <thead><tr>
                        <?php foreach (array_keys($data[0]) as $col): ?>
                            <th><?= htmlspecialchars((string)$col) ?></th>
                        <?php endforeach; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($row as $val): ?>
                                <td><?= htmlspecialchars((string)$val) ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: echo "<p>Нет данных</p>"; endif; ?>
        </div>
    </div>
    <a href="index.php" class="btn btn-secondary mt-3">На главную</a>
</div>
</body>
</html>