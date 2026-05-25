<?php
require 'config.php';
$auth_user = 'admin';
$auth_pass = 'root';

if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $auth_user || $_SERVER['PHP_AUTH_PW'] != $auth_pass) {
    header('WWW-Authenticate: Basic realm="Введите логин admin и пароль root"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Доступ запрещён';
    exit;
}
$result = null;
$sql = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql'])) {
    $sql = $_POST['sql'];
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        if (stripos($sql, 'select') === 0) {
            $result = $stmt->fetchAll();
        } else {
            $result = "Выполнено успешно, затронуто строк: " . $stmt->rowCount();
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
    <title>Произвольный SQL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Выполнение SQL-запросов (только для администратора)</h2>
    <form method="post">
        <div class="mb-3">
            <textarea name="sql" rows="6" class="form-control" placeholder="SELECT * FROM client LIMIT 10"><?= htmlspecialchars($sql) ?></textarea>
        </div>
        <button type="submit" class="btn btn-danger">Выполнить</button>
        <a href="index.php" class="btn btn-secondary">На главную</a>
    </form>
    <?php if ($result !== null): ?>
        <hr><h4>Результат:</h4>
        <?php if (is_array($result)): ?>
            <table class="table table-bordered">
                <thead><tr><?php foreach (array_keys($result[0] ?? []) as $col): ?><th><?= htmlspecialchars($col) ?></th><?php endforeach; ?></tr></thead>
                <tbody><?php foreach ($result as $row): ?>
                    <tr><?php foreach ($row as $val): ?><td><?= htmlspecialchars($val) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?></tbody>
            </table>
        <?php else: echo "<pre>" . htmlspecialchars($result) . "</pre>"; endif; ?>
    <?php endif; ?>
</div>
</body>
</html>