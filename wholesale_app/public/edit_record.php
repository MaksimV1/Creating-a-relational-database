<?php
require 'config.php';
$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? null;
$pk = $_GET['pk'] ?? 'id';

if (!$table) die("Таблица не указана");

// Получаем структуру таблицы
$stmt = $pdo->query("DESCRIBE `$table`");
$columns = $stmt->fetchAll();
$colNames = array_column($columns, 'Field');

$oldData = [];
if ($id) {
    $sql = "SELECT * FROM `$table` WHERE `$pk` = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $oldData = $stmt->fetch();
    if (!$oldData) die("Запись не найдена");
}

foreach ($_POST as $key => $value) {
    // Проверяем, является ли поле числовым (по типу из DESCRIBE)
    foreach ($columns as $col) {
        if ($col['Field'] == $key) {
            if (strpos($col['Type'], 'decimal') !== false || strpos($col['Type'], 'int') !== false) {
                if ($value === '' || $value === null) {
                    $_POST[$key] = 0;
                } elseif (!is_numeric($value)) {
                    die("Ошибка: поле $key должно быть числовым");
                } elseif ($col['Field'] == 'balance' && $value > 99999999.99) {
                    $_POST[$key] = 99999999.99; // ограничиваем максимальное значение
                }
            }
            break;
        }
    }
}

if (isset($_POST['balance']) && $_POST['balance'] > 99999999.99) {
    $_POST['balance'] = 99999999.99;
}
if (isset($_POST['balance']) && $_POST['balance'] < -99999999.99) {
    $_POST['balance'] = -99999999.99;
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [];
    $placeholders = [];
    $values = [];
    foreach ($colNames as $col) {
        if ($col === $pk && $id) continue; // не обновляем PK
        if (isset($_POST[$col])) {
            $fields[] = "`$col` = :$col";
            $placeholders[":$col"] = $_POST[$col];
        }
    }
    if ($id) {
        $sql = "UPDATE `$table` SET " . implode(', ', $fields) . " WHERE `$pk` = :pk_id";
        $placeholders[':pk_id'] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($placeholders);
        $message = "Запись обновлена";
    } else {
        $fieldNames = array_keys($_POST);
        $fieldList = implode(', ', array_map(fn($f) => "`$f`", $fieldNames));
        $valuePlaceholders = ':' . implode(', :', $fieldNames);
        $sql = "INSERT INTO `$table` ($fieldList) VALUES ($valuePlaceholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($_POST);
        $message = "Запись добавлена";
    }
    header("Location: view_table.php?table=" . urlencode($table) . "&msg=" . urlencode($message));
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $id ? "Редактировать" : "Добавить" ?> запись</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2><?= $id ? "Редактирование" : "Добавление" ?> в таблицу <?= htmlspecialchars($table) ?></h2>
    <form method="post">
        <?php foreach ($colNames as $col): ?>
            <?php if ($col === $pk && $id) continue; ?>
            <div class="mb-3">
                <label class="form-label"><?= htmlspecialchars($col) ?></label>
                <input type="text" name="<?= htmlspecialchars($col) ?>" class="form-control"
                       value="<?= htmlspecialchars($oldData[$col] ?? '') ?>">
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Сохранить</button>
        <a href="view_table.php?table=<?= urlencode($table) ?>" class="btn btn-secondary">Отмена</a>
    </form>
</div>
</body>
</html>