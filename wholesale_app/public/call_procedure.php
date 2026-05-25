<?php
require 'config.php';
$procedure = $_POST['procedure'] ?? '';
$result = null;
$params = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $procedure) {
    try {
        if ($procedure === 'UpsertClient') {
            $stmt = $pdo->prepare("CALL UpsertClient(?,?,?,?,?,?)");
            $stmt->execute([$_POST['id'] ?: null, $_POST['name'], $_POST['phone'], $_POST['balance'], $_POST['region_id'], $_POST['address_id']]);
            $result = $stmt->fetch();
        } elseif ($procedure === 'ОтчётПоКлиенту') {
            $stmt = $pdo->prepare("CALL ОтчётПоКлиенту(?)");
            $stmt->execute([$_POST['client_id']]);
            $result = $stmt->fetchAll();
        } elseif ($procedure === 'ProcessBackorders') {
            $stmt = $pdo->prepare("CALL ProcessBackorders(?)");
            $stmt->execute([$_POST['product_id']]);
            $result = "Бэк-заказы обработаны для товара ID " . $_POST['product_id'];
        } elseif ($procedure === 'GetClientOrders') {
            $stmt = $pdo->prepare("CALL GetClientOrders(?)");
            $stmt->execute([$_POST['client_id']]);
            $result = $stmt->fetchAll();
        } else {
            $result = "Неизвестная процедура";
        }
    } catch (PDOException $e) {
        $result = "Ошибка: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вызов процедур</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Хранимые процедуры</h2>
    <form method="post">
        <div class="mb-3">
            <label>Выберите процедуру</label>
            <select name="procedure" class="form-select" id="procSelect">
                <option value="UpsertClient">UpsertClient (добавить/обновить клиента)</option>
                <option value="ОтчётПоКлиенту">ОтчётПоКлиенту</option>
                <option value="ProcessBackorders">ProcessBackorders (обработка отложенных)</option>
                <option value="GetClientOrders">GetClientOrders (заказы клиента)</option>
            </select>
        </div>
        <div id="paramsContainer"></div>
        <button type="submit" class="btn btn-primary">Выполнить</button>
        <a href="index.php" class="btn btn-secondary">На главную</a>
    </form>

    <?php if ($result): ?>
        <hr><h4>Результат:</h4>
        <pre><?php if (is_array($result)) print_r($result); else echo htmlspecialchars($result); ?></pre>
    <?php endif; ?>
</div>

<script>
    const proc = document.getElementById('procSelect');
    const container = document.getElementById('paramsContainer');
    function updateForm() {
        let val = proc.value;
        let html = '';
        if (val === 'UpsertClient') {
            html = `<div class="mb-3"><label>ID (0 для новой)</label><input name="id" class="form-control"></div>
                    <div class="mb-3"><label>Имя</label><input name="name" class="form-control"></div>
                    <div class="mb-3"><label>Телефон</label><input name="phone" class="form-control"></div>
                    <div class="mb-3"><label>Баланс</label><input name="balance" class="form-control"></div>
                    <div class="mb-3"><label>ID региона</label><input name="region_id" class="form-control"></div>
                    <div class="mb-3"><label>ID адреса</label><input name="address_id" class="form-control"></div>`;
        } else if (val === 'ОтчётПоКлиенту' || val === 'GetClientOrders') {
            html = `<div class="mb-3"><label>ID клиента</label><input name="client_id" class="form-control"></div>`;
        } else if (val === 'ProcessBackorders') {
            html = `<div class="mb-3"><label>ID товара</label><input name="product_id" class="form-control"></div>`;
        }
        container.innerHTML = html;
    }
    proc.addEventListener('change', updateForm);
    updateForm();
</script>
</body>
</html>