<?php
require 'config.php';
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $employee_id = $_POST['employee_id'];
    $sales_rep_number = $_POST['sales_rep_number'];
    $payment_method = $_POST['payment_method'];
    $shortage_action = $_POST['shortage_action'];
    
    // Формируем JSON из товаров (получаем динамические строки)
    $products = [];
    $names = $_POST['product_id'] ?? [];
    $qties = $_POST['quantity'] ?? [];
    for ($i = 0; $i < count($names); $i++) {
        if (!empty($names[$i]) && !empty($qties[$i]) && is_numeric($qties[$i]) && $qties[$i] > 0) {
            $products[] = ['product_id' => (int)$names[$i], 'quantity' => (int)$qties[$i]];
        }
    }

    if (empty($products)) {
        $result = ['error' => 'Добавьте хотя бы один товар с положительным количеством'];
    } else {
        $items_json = json_encode($products);
        try {
            $sql = "CALL ДобавитьЗаказ(:client, :employee, :sales_rep, :payment, :items, :action)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':client' => $client_id,
                ':employee' => $employee_id,
                ':sales_rep' => $sales_rep_number,
                ':payment' => $payment_method,
                ':items' => $items_json,
                ':action' => $shortage_action
            ]);
            $result = $pdo->query("SELECT @order_id AS order_id")->fetch();
        } catch (PDOException $e) {
            $result = ['error' => $e->getMessage()];
        }
    }
}
// Получаем списки для выпадающих меню
$clients = $pdo->query("SELECT id, name FROM client")->fetchAll();
$employees = $pdo->query("SELECT id, name, surname FROM employee")->fetchAll();
$salesReps = $pdo->query("SELECT number, CONCAT('Rep #', number) as label FROM sales_rep")->fetchAll();
$productsList = $pdo->query("SELECT id, name, price FROM product")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новый заказ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function addProductRow() {
            let container = document.getElementById('products-container');
            let newRow = document.createElement('div');
            newRow.className = 'row mb-2';
            newRow.innerHTML = `
                <div class="col"><select name="product_id[]" class="form-select"><?php foreach ($productsList as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col"><input type="number" name="quantity[]" class="form-control" placeholder="Количество"></div>
                <div class="col"><button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.remove()">Удалить</button></div>
            `;
            container.appendChild(newRow);
        }
    </script>
</head>
<body>
<div class="container mt-4">
    <h2>Создание заказа</h2>
    <?php if ($result): ?>
        <div class="alert alert-info">
            <?php if (isset($result['error'])) echo "Ошибка: " . htmlspecialchars($result['error']);
            else echo "Заказ создан. Номер заказа: " . htmlspecialchars($result['order_id']); ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <div class="mb-3">
            <label>Клиент</label>
            <select name="client_id" class="form-select" required>
                <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Сотрудник (менеджер)</label>
            <select name="employee_id" class="form-select" required>
                <?php foreach ($employees as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name'] . " " . $e['surname']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Торговый представитель</label>
            <select name="sales_rep_number" class="form-select" required>
                <?php foreach ($salesReps as $sr): ?>
                    <option value="<?= $sr['number'] ?>"><?= htmlspecialchars($sr['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Способ оплаты</label>
            <select name="payment_method" class="form-select">
                <option>Безналичный</option><option>Наличный</option><option>Кредитная карта</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Действие при дефиците</label>
            <select name="shortage_action" class="form-select">
                <option value="CANCEL">Отменить заказ</option>
                <option value="PARTIAL">Частичная отгрузка</option>
                <option value="WAIT">Ожидание</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Товары в заказе</label>
            <div id="products-container">
                <div class="row mb-2">
                    <div class="col"><select name="product_id[]" class="form-select"><?php foreach ($productsList as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col"><input type="number" name="quantity[]" class="form-control" placeholder="Количество"></div>
                    <div class="col"><button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.remove()">Удалить</button></div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary mt-2" onclick="addProductRow()">+ Добавить товар</button>
        </div>
        <button type="submit" class="btn btn-primary">Оформить заказ</button>
        <a href="index.php" class="btn btn-secondary">На главную</a>
    </form>
</div>
</body>
</html>