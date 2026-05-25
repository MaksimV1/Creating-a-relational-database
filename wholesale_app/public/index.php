<?php
require 'config.php';

// Автоматический сброс базы только при первом запуске (через сессию)
session_start();
if (!isset($_SESSION['db_initialized'])) {
    try {
        $pdo->exec("CALL ResetDatabaseToInitialState()");
        $_SESSION['db_initialized'] = true;
    } catch (PDOException $e) {
        // Если процедура не существует, игнорируем
    }
}

$current_virtual_time = getVirtualTime($pdo);

// Получаем список всех представлений
$views = [];
$stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $views[] = $row[0];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Оптовая фирма – управление</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .company-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 20px 0;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .company-header h1 {
            font-size: 3rem;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 0;
        }
        .sidebar {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: 100%;
        }
        .view-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: 0.2s;
        }
        .view-item:hover {
            background: #e9ecef;
        }
        .view-name {
            font-weight: 500;
            font-size: 0.95rem;
        }
        .btn-view {
            padding: 2px 12px;
            font-size: 0.8rem;
        }
        .main-area {
            background: white;
            border-radius: 12px;
            padding: 20px;
            min-height: 500px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .action-buttons .btn {
            flex: 1;
            min-width: 120px;
        }
        .time-panel {
            margin-top: 30px;
            padding: 15px;
            background: #f0f2f5;
            border-radius: 12px;
            text-align: center;
        }
        .virtual-time-toggle {
            cursor: pointer;
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .virtual-time-toggle:hover {
            background: #5a6268;
        }
        .time-details {
            margin-top: 15px;
            display: none;
        }
        .sql-console {
            margin-top: 15px;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }
        .sql-textarea {
            font-family: monospace;
        }
        footer {
            text-align: center;
            margin-top: 40px;
            padding: 15px;
            color: #6c757d;
            font-size: 0.8rem;
        }
        @media (max-width: 768px) {
            .sidebar { margin-bottom: 20px; }
            .action-buttons .btn { min-width: auto; }
        }
    </style>
</head>
<body>

<div class="company-header">
    <div class="container">
        <h1>🏬 Оптовая фирма</h1>
        <p class="mb-0">Спортивные товары – управление заказами и складом</p>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Левая колонка: список представлений -->
        <div class="col-md-3">
            <div class="sidebar">
                <h5 class="mb-3">📊 Представления</h5>
                <div id="views-list">
                    <?php foreach ($views as $view): ?>
                        <div class="view-item">
                            <span class="view-name"><?= htmlspecialchars($view) ?></span>
                            <button class="btn btn-sm btn-outline-primary btn-view" data-view="<?= htmlspecialchars($view) ?>">Перейти</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Правая колонка: основной контент -->
        <div class="col-md-9">
            <div class="main-area">
                <div class="action-buttons">
                    <!-- Таблицы с выпадающим списком -->
                    <form action="view_table.php" method="get" class="d-inline-flex gap-2 align-items-center">
                        <select name="table" class="form-select form-select-sm w-auto" required>
                            <option value="">Выберите таблицу...</option>
                            <?php
                            $tables = ['client', 'address', 'region', 'warehouse', 'product', 'department', 'employee', 'sales_rep', 'payment_profile', 'order', 'order_item', 'warehouse_stock'];
                            foreach ($tables as $t) {
                                echo "<option value=\"$t\">$t</option>";
                            }
                            ?>
                        </select>
                        <button type="submit" class="btn btn-primary">📋 Таблицы</button>
                    </form>
                    <!-- Остальные кнопки -->
                    <a href="add_order.php" class="btn btn-success">🛒 Новый заказ</a>
                    <a href="reports.php" class="btn btn-info">📊 Отчёты</a>
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#replenishModal">📦 Пополнить склады</button>
                </div>

                <!-- Область для отображения содержимого представления -->
                <div id="view-content">
                    <div class="alert alert-secondary text-center">
                        👈 Выберите представление из списка слева для просмотра
                    </div>
                </div>

                <!-- Кнопки нижнего ряда -->
                <div class="action-buttons mt-3">
                    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#proceduresModal">⚙️ Хранимые процедуры</button>
                    <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#testsModal">🧪 Тесты</button>
                </div>

                <!-- Кнопка произвольного SQL -->
                <div class="sql-console">
                    <button class="btn btn-dark" id="sqlConsoleBtn">🔧 Произвольный SQL (администрирование)</button>
                    <div id="sqlConsolePanel" style="display: none; margin-top: 15px;">
                        <div class="mb-2">
                            <label for="sqlPassword" class="form-label">Пароль администратора:</label>
                            <input type="password" id="sqlPassword" class="form-control" placeholder="Введите пароль">
                        </div>
                        <div class="mb-2">
                            <label for="sqlQuery" class="form-label">SQL-запрос:</label>
                            <textarea id="sqlQuery" rows="4" class="form-control sql-textarea" placeholder="SELECT * FROM client LIMIT 10"></textarea>
                        </div>
                        <button class="btn btn-primary" id="executeSqlBtn">Выполнить</button>
                        <div id="sqlResult" class="mt-3"></div>
                    </div>
                </div>

                <!-- Панель виртуального времени (внизу) -->
                <div class="time-panel">
                    <button class="virtual-time-toggle" id="toggleTimePanel">🕒 Виртуальное время</button>
                    <div id="timeDetails" class="time-details">
                        <div class="mb-2">
                            <strong>Текущее виртуальное время:</strong>
                            <span id="current-time-label" class="badge bg-secondary fs-6"><?= htmlspecialchars($current_virtual_time) ?></span>
                            <button id="reset-time-btn" class="btn btn-sm btn-warning ms-2">Сброс (реальное время)</button>
                        </div>
                        <div id="time-slider"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно: пополнение складов -->
<div class="modal fade" id="replenishModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Пополнение склада</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="replenishForm">
                    <div class="mb-3">
                        <label class="form-label">Склад</label>
                        <select name="warehouse_id" class="form-select" required>
                            <?php
                            $wh = $pdo->query("SELECT id, name FROM warehouse")->fetchAll();
                            foreach ($wh as $w) echo "<option value='{$w['id']}'>{$w['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Товар</label>
                        <select name="product_id" class="form-select" required>
                            <?php
                            $prod = $pdo->query("SELECT id, name FROM product")->fetchAll();
                            foreach ($prod as $p) echo "<option value='{$p['id']}'>{$p['name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Количество для добавления</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                    <button type="submit" class="btn btn-success">Пополнить</button>
                </form>
                <div id="replenishResult"></div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно: хранимые процедуры -->
<div class="modal fade" id="proceduresModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Хранимые процедуры</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Выберите процедуру для выполнения:</p>
                <select id="procSelect" class="form-select mb-3">
                    <option value="UpsertClient">UpsertClient – добавить/обновить клиента (требует имя, телефон, баланс, регион, адрес)</option>
                    <option value="ОтчётПоКлиенту">ОтчётПоКлиенту – показать все заказы клиента с суммами</option>
                    <option value="ProcessBackorders">ProcessBackorders – обработать отложенные заказы для указанного товара</option>
                    <option value="GetClientOrders">GetClientOrders – список заказов клиента (упрощённый)</option>
                </select>
                <div id="procParams"></div>
                <button id="executeProcBtn" class="btn btn-primary">Выполнить</button>
                <div id="procResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно: тесты -->
<div class="modal fade" id="testsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Тестирование БД</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">🧪 Тесты</h5>
                        <p class="card-text">Полный набор из 9 блоков тестов:<br>
                        CRUD, бизнес-логика, отгрузка, отчёты, ограничения и комплексный сценарий.</p>
                        <a href="run_tests.php" target="_blank" class="btn btn-warning">Запустить тесты (новая вкладка)</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function() {
    // ========== ПРЕДСТАВЛЕНИЯ ==========
    $('.btn-view').click(function() {
        let viewName = $(this).data('view');
        $('#view-content').html('<div class="text-center"><div class="spinner-border text-primary"></div><p>Загрузка...</p></div>');
        $.get('get_view.php', { view: viewName }, function(data) {
            $('#view-content').html(data);
        }).fail(function() {
            $('#view-content').html('<div class="alert alert-danger">Ошибка загрузки представления</div>');
        });
    });

    // ========== ПОПОЛНЕНИЕ СКЛАДА ==========
    $('#replenishForm').submit(function(e) {
        e.preventDefault();
        $.post('replenish_stock.php', $(this).serialize(), function(res) {
            $('#replenishResult').html('<div class="alert alert-success mt-2">' + res.message + '</div>');
            setTimeout(() => $('#replenishModal').modal('hide'), 1500);
        }, 'json').fail(function() {
            $('#replenishResult').html('<div class="alert alert-danger mt-2">Ошибка пополнения</div>');
        });
    });

    // ========== ПРОИЗВОЛЬНЫЙ SQL ==========
    $('#sqlConsoleBtn').click(function() {
        $('#sqlConsolePanel').slideToggle();
    });
    $('#executeSqlBtn').click(function() {
        let password = $('#sqlPassword').val();
        let sql = $('#sqlQuery').val();
        if (!password || !sql) {
            alert('Введите пароль и SQL-запрос');
            return;
        }
        $('#sqlResult').html('<div class="spinner-border text-primary"></div>');
        $.post('execute_sql.php', { password: password, sql: sql }, function(data) {
            $('#sqlResult').html(data);
        }).fail(function() {
            $('#sqlResult').html('<div class="alert alert-danger">Ошибка выполнения запроса</div>');
        });
    });

    // ========== ВИРТУАЛЬНОЕ ВРЕМЯ ==========
    let minDate, maxDate;
    let currentTime = '<?= $current_virtual_time ?>';

    $.get('ajax_time_slider.php', { action: 'get_min_max_dates' }, function(data) {
        if (data.success) {
            minDate = new Date(data.min_date);
            maxDate = new Date(data.max_date);
            let currentDate = new Date(currentTime);
            if (currentDate < minDate) currentDate = minDate;
            if (currentDate > maxDate) currentDate = maxDate;

            $("#time-slider").datepicker({
                minDate: minDate,
                maxDate: maxDate,
                defaultDate: currentDate,
                onSelect: function(dateText) {
                    let parts = dateText.split('/');
                    let formattedDate = parts[2] + '-' + parts[0] + '-' + parts[1] + ' 23:59:59';
                    $.post('ajax_time_slider.php', { action: 'set_time', new_time: formattedDate }, function(res) {
                        if (res.success) {
                            $("#current-time-label").text(res.virtual_time);
                            location.reload();
                        } else {
                            alert("Ошибка: " + res.error);
                        }
                    }, 'json');
                }
            });
        }
    }, 'json');

    $('#toggleTimePanel').click(function() {
        $('#timeDetails').slideToggle();
    });

    $('#reset-time-btn').click(function() {
        $.post('ajax_time_slider.php', { action: 'reset_time' }, function(res) {
            if (res.success) {
                $("#current-time-label").text(res.virtual_time);
                location.reload();
            } else {
                alert("Ошибка: " + res.error);
            }
        }, 'json');
    });

    // ========== ХРАНИМЫЕ ПРОЦЕДУРЫ ==========
    function updateProcParams() {
        let proc = $('#procSelect').val();
        let html = '';
        if (proc === 'UpsertClient') {
            html = `
                <div class="mb-2"><label>ID (0 для новой)</label><input class="form-control" name="id"></div>
                <div class="mb-2"><label>Имя</label><input class="form-control" name="name"></div>
                <div class="mb-2"><label>Телефон</label><input class="form-control" name="phone"></div>
                <div class="mb-2"><label>Баланс</label><input class="form-control" name="balance"></div>
                <div class="mb-2"><label>ID региона</label><input class="form-control" name="region_id"></div>
                <div class="mb-2"><label>ID адреса</label><input class="form-control" name="address_id"></div>
            `;
        } else if (proc === 'ОтчётПоКлиенту' || proc === 'GetClientOrders') {
            html = `<div class="mb-2"><label>ID клиента</label><input class="form-control" name="client_id"></div>`;
        } else if (proc === 'ProcessBackorders') {
            html = `<div class="mb-2"><label>ID товара</label><input class="form-control" name="product_id"></div>`;
        }
        $('#procParams').html(html);
    }
    $('#procSelect').change(updateProcParams);
    updateProcParams();

    $('#executeProcBtn').click(function() {
        let proc = $('#procSelect').val();
        let params = {};
        $('#procParams input').each(function() { params[$(this).attr('name')] = $(this).val(); });
        $.post('call_procedure_ajax.php', { procedure: proc, params: params }, function(data) {
            $('#procResult').html('<pre>' + JSON.stringify(data, null, 2) + '</pre>');
        }, 'json').fail(function() {
            $('#procResult').html('<div class="alert alert-danger">Ошибка вызова процедуры</div>');
        });
    });
});
</script>

<footer>
    &copy; 2026 Оптовая фирма. Система управления заказами и складом.
</footer>

</body>
</html>