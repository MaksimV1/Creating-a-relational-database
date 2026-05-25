<?php
require 'config.php';

// ----------------------------------------------------------------------
// Вспомогательные функции
// ----------------------------------------------------------------------
function section($title) {
    echo "<div style='background: #e9ecef; padding: 10px; margin: 30px 0 15px 0; border-radius: 5px;'><h2>$title</h2></div>";
}

/**
 * Выполняет тестовый запрос, обрабатывает ожидаемые/неожиданные ошибки,
 * а также гарантированно очищает все результаты (pending result sets).
 */
function runTest($pdo, $sql, $params = [], $description = '', $expectError = false) {
    echo "<div style='margin-bottom: 15px; border-left: 4px solid #007bff; padding-left: 12px;'>";
    if ($description) echo "<strong>🔹 $description</strong><br>";
    echo "<code style='background:#f4f4f4; padding:2px 4px;'>" . htmlspecialchars($sql) . "</code><br>";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Критически важно: пролистываем все резалт-сеты, иначе следующий запрос вызовет ошибку 2014
        while ($stmt->nextRowset()) { /* просто сбрасываем */ }
        
        // Если ожидалась ошибка, но её не произошло
        if ($expectError) {
            echo "<div style='color: #d9534f; font-weight: bold;'>❌ НЕОЖИДАННЫЙ УСПЕХ – ошибка должна была быть, но запрос выполнился</div>";
        } else {
            // Обработка SELECT-запросов
            if (stripos($sql, 'select') === 0 || stripos($sql, 'show') === 0) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) {
                    echo "<div style='margin-top: 8px;'><table border='1' cellpadding='3' style='border-collapse: collapse; font-size: 12px;'>";
                    echo "<tr>";
                    foreach (array_keys($rows[0]) as $col) echo "<th>" . htmlspecialchars($col) . "</th>";
                    echo "</tr>";
                    foreach ($rows as $row) {
                        echo "<tr>";
                        foreach ($row as $val) echo "<td>" . htmlspecialchars($val) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table></div>";
                } else {
                    echo "<span style='color: gray;'>Нет данных</span>";
                }
            } else {
                $count = $stmt->rowCount();
                echo "<span style='color: green;'>✓ Успешно. Затронуто строк: $count</span>";
            }
            echo "<div style='color: green; font-weight: bold;'>✅ РЕЗУЛЬТАТ: успех</div>";
        }
        
        $stmt->closeCursor();
        
    } catch (PDOException $e) {
        if ($expectError) {
            echo "<div style='color: #856404; background: #fff3cd; padding: 6px; border-radius: 4px; font-weight: bold;'>⚠️ ОЖИДАЕМАЯ ОШИБКА: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div style='color: #856404;'>✅ РЕЗУЛЬТАТ: ошибка (ожидаемая)</div>";
        } else {
            echo "<div style='color: red;'>❌ НЕОЖИДАННАЯ ОШИБКА: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    echo "</div><hr style='margin: 8px 0;'>";
}

// ----------------------------------------------------------------------
// Начало страницы
// ----------------------------------------------------------------------
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Полное тестирование оптовой фирмы</title>";
echo "<style>body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #fff; }</style>";
echo "</head><body>";
echo "<h1>🧪 Полное тестирование системы управления оптовой фирмой</h1>";
echo "<p><strong>Дата и время выполнения:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><em>Тесты разделены на 9 блоков. Ожидаемые ошибки выделены жирным шрифтом.</em></p><hr>";

// ----------------------------------------------------------------------
// 1. Тесты целостности структуры данных и CRUD-операций
// ----------------------------------------------------------------------
section("1. Тесты целостности структуры данных и CRUD-операций");

runTest($pdo, "INSERT INTO region (name) VALUES ('Тестовый регион')", [], "1.1 Создание региона");
runTest($pdo, "INSERT INTO address (city, country, street, house) VALUES ('Test City', 'Test Country', 'Main St', 1)", [], "1.2 Создание адреса");
runTest($pdo, "INSERT INTO client (name, phone, balance, region_id, address_id) VALUES ('Test Client', '+123', 1000, 1, 1)", [], "1.3 Создание клиента");
runTest($pdo, "UPDATE client SET balance = 2000 WHERE name = 'Test Client'", [], "1.4 Обновление клиента");
runTest($pdo, "DELETE FROM client WHERE name = 'Test Client'", [], "1.5 Удаление клиента");
runTest($pdo, "INSERT INTO employee (surname, name, hire_date, position, salary, department_id) VALUES ('Test', 'User', CURDATE(), 'Tester', 50000, 1)", [], "1.6 Создание сотрудника");
runTest($pdo, "INSERT INTO product (name, price, min_level, max_level) VALUES ('Test Product', 100, 5, 100)", [], "1.7 Создание товара");
$testProductId = $pdo->lastInsertId();
runTest($pdo, "INSERT INTO warehouse (name, region_id, address_id) VALUES ('Test Warehouse', 1, 1)", [], "1.8 Создание склада");
$testWarehouseId = $pdo->lastInsertId();
// 1.9 – используем только что созданные товар и склад
runTest($pdo, "INSERT INTO warehouse_stock (warehouse_link, product_link, `stored`, `reserved`) VALUES ($testWarehouseId, $testProductId, 100, 0)", [], "1.9 Создание остатков (новая пара склад-товар)");
// 1.10 – повторная вставка той же пары вызывает ошибку дубликата (ожидаемо)
runTest($pdo, "INSERT INTO warehouse_stock (warehouse_link, product_link, `stored`, `reserved`) VALUES ($testWarehouseId, $testProductId, 50, 0)", [], "1.10 Уникальность пары склад-товар (должна быть ошибка дубликата)", true);

// ----------------------------------------------------------------------
// 2. Тесты бизнес-логики создания заказа с резервированием
// ----------------------------------------------------------------------
section("2. Тесты бизнес-логики создания заказа с резервированием");

// Подготовка данных
runTest($pdo, "INSERT INTO client (name, phone, balance, region_id, address_id) VALUES ('BizClient', '+100', 10000, 1, 1)", [], "Подготовка: клиент");
$clientId = $pdo->lastInsertId();
runTest($pdo, "INSERT INTO product (name, price, min_level, max_level) VALUES ('BizProduct', 100, 1, 1000)", [], "Подготовка: товар");
$productId = $pdo->lastInsertId();
runTest($pdo, "INSERT INTO warehouse_stock (warehouse_link, product_link, `stored`, `reserved`) VALUES ($testWarehouseId, $productId, 100, 0)", [], "Подготовка: остатки 100 шт на складе");

$empId = $pdo->query("SELECT id FROM employee LIMIT 1")->fetchColumn();
$srNum = $pdo->query("SELECT number FROM sales_rep LIMIT 1")->fetchColumn();

$itemsJson = function($qty) use ($productId) { return json_encode([["product_id" => $productId, "quantity" => $qty]]); };

runTest($pdo, "CALL ДобавитьЗаказ($clientId, $empId, $srNum, 'Безналичный', '{$itemsJson(50)}', 'PARTIAL')", [], "2.1 Создание заказа при достаточном количестве (50 из 100)");
runTest($pdo, "CALL ДобавитьЗаказ($clientId, $empId, $srNum, 'Безналичный', '{$itemsJson(150)}', 'CANCEL')", [], "2.2 Создание заказа с дефицитом, действие CANCEL (ожидается ошибка)", true);
runTest($pdo, "CALL ДобавитьЗаказ($clientId, $empId, $srNum, 'Безналичный', '{$itemsJson(120)}', 'WAIT')", [], "2.3 Создание заказа с дефицитом, действие WAIT");
// Уменьшим остатки для PARTIAL
runTest($pdo, "UPDATE warehouse_stock SET `stored` = 20, `reserved` = 0 WHERE warehouse_link = $testWarehouseId AND product_link = $productId", [], "Устанавливаем остатки = 20");
runTest($pdo, "CALL ДобавитьЗаказ($clientId, $empId, $srNum, 'Безналичный', '{$itemsJson(50)}', 'PARTIAL')", [], "2.4 Создание заказа с дефицитом, действие PARTIAL (частичная отгрузка)");
// Приоритет региона
runTest($pdo, "INSERT INTO warehouse (name, region_id, address_id) VALUES ('OtherRegion', 2, 1)", [], "Подготовка: склад в другом регионе");
$otherWh = $pdo->lastInsertId();
runTest($pdo, "INSERT INTO warehouse_stock (warehouse_link, product_link, `stored`, `reserved`) VALUES ($otherWh, $productId, 100, 0)", [], "Остатки на другом складе 100");
runTest($pdo, "UPDATE warehouse_stock SET `stored` = 30, `reserved` = 0 WHERE warehouse_link = $testWarehouseId AND product_link = $productId", [], "На складе региона клиента оставляем 30");
runTest($pdo, "CALL ДобавитьЗаказ($clientId, $empId, $srNum, 'Безналичный', '{$itemsJson(40)}', 'PARTIAL')", [], "2.5 Проверка приоритета резервирования (сначала регион клиента)");

// ----------------------------------------------------------------------
// 3. Тесты отгрузки и списания товаров
// ----------------------------------------------------------------------
section("3. Тесты отгрузки и списания товаров");

runTest($pdo, "UPDATE warehouse_stock SET `stored` = 100, `reserved` = 0 WHERE warehouse_link = $testWarehouseId AND product_link = $productId", [], "Восстанавливаем остатки");
$orderForShip = null;
try {
    $stmt = $pdo->prepare("CALL ДобавитьЗаказ(?, ?, ?, 'Безналичный', ?, 'PARTIAL')");
    $stmt->execute([$clientId, $empId, $srNum, $itemsJson(30)]);
    while ($stmt->nextRowset()) { }
    $stmt->closeCursor();
    $orderForShip = $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
    echo "<div>Создан заказ ID: $orderForShip</div>";
} catch(Exception $e) { echo "<div>Ошибка создания заказа: ".$e->getMessage()."</div>"; }

if ($orderForShip) {
    runTest($pdo, "UPDATE order_item SET shipped = 30 WHERE order_link = $orderForShip AND product_id = $productId", [], "3.1 Ручная отгрузка (обновление shipped) – триггер списывает резерв и остатки");
    runTest($pdo, "SELECT * FROM warehouse_stock WHERE product_link = $productId", [], "Проверка остатков после отгрузки");
    runTest($pdo, "UPDATE order_item SET shipped = 30 WHERE order_link = $orderForShip AND product_id = $productId", [], "3.2 Полная отгрузка всего заказа (повторно)");
} else {
    echo "<div style='color:red;'>Не удалось создать заказ для тестов отгрузки, пропускаем 3.1-3.2</div>";
}

// Автоотгрузка через виртуальное время – создадим заказ с будущей датой отгрузки
runTest($pdo, "INSERT INTO `order` (client_link, employee_id, sales_rep_number, order_date, shipment_date, payment_method) VALUES ($clientId, $empId, $srNum, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 'Безналичный')", [], "Создаём заказ с shipment_date = завтра");
$futureOrder = $pdo->lastInsertId();
if ($futureOrder) {
    runTest($pdo, "INSERT INTO order_item (order_link, product_id, quantity, shipped) VALUES ($futureOrder, $productId, 20, 0)", [], "Добавляем позицию 20 шт");
    runTest($pdo, "CALL SetVirtualTime(DATE_ADD(NOW(), INTERVAL 2 DAY))", [], "3.3 Устанавливаем виртуальное время на 2 дня вперёд (автоматическая отгрузка)");
    runTest($pdo, "SELECT shipped FROM order_item WHERE order_link = $futureOrder", [], "Проверка: shipped должно стать 20");
    runTest($pdo, "CALL ResetVirtualTime()", [], "Сброс виртуального времени к реальному");
} else {
    echo "<div style='color:red;'>Не удалось создать заказ для теста автоотгрузки, пропускаем</div>";
}

// ----------------------------------------------------------------------
// 4. Тесты обработки отложенных заказов (ProcessBackorders)
// ----------------------------------------------------------------------
section("4. Тесты обработки отложенных заказов (ProcessBackorders)");

if ($futureOrder) {
    runTest($pdo, "UPDATE warehouse_stock SET `stored` = 0, `reserved` = 0 WHERE warehouse_link = $testWarehouseId AND product_link = $productId", [], "Обнуляем остатки для товара");
    runTest($pdo, "DELETE FROM backorder_item WHERE product_id = $productId", [], "Очищаем старые backorder");
    runTest($pdo, "INSERT INTO backorder_item (order_link, product_id, quantity) VALUES ($futureOrder, $productId, 20)", [], "Создаём запись в backorder_item на 20");
    runTest($pdo, "UPDATE warehouse_stock SET `stored` = `stored` + 50 WHERE warehouse_link = $testWarehouseId AND product_link = $productId", [], "Пополняем склад на 50 единиц");
    runTest($pdo, "CALL ProcessBackorders($productId)", [], "Запускаем ProcessBackorders (должен обработать отложенный заказ)");
    runTest($pdo, "SELECT * FROM backorder_item WHERE product_id = $productId", [], "Проверка: запись должна исчезнуть");
} else {
    echo "<div style='color:red;'>Нет корректного заказа для теста backorder, пропускаем блок 4</div>";
}

// ----------------------------------------------------------------------
// 5. Тесты хранимых функций и процедур
// ----------------------------------------------------------------------
section("5. Тесты хранимых функций и процедур");
if ($orderForShip) {
    runTest($pdo, "SELECT GetOrderTotal($orderForShip) AS total", [], "5.1 GetOrderTotal");
} else {
    echo "<div>Заказ для GetOrderTotal не создан, тест 5.1 пропущен</div>";
}
runTest($pdo, "SELECT ЕстьЗадолженность($clientId) AS has_debt", [], "5.2 ЕстьЗадолженность");
runTest($pdo, "CALL ОтчётПоКлиенту($clientId)", [], "5.3 ОтчётПоКлиенту");
runTest($pdo, "SELECT GetVirtualTime() AS vt", [], "5.4 GetVirtualTime");
runTest($pdo, "CALL SetVirtualTime('2020-01-01 12:00:00')", [], "5.5 SetVirtualTime (граничные условия – попытка установить время в прошлое, ожидается ошибка)", true);

// ----------------------------------------------------------------------
// 6. Тесты представлений (VIEW)
// ----------------------------------------------------------------------
section("6. Тесты представлений (VIEW)");
$views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($views as $view) {
    runTest($pdo, "SELECT * FROM `$view` LIMIT 10", [], "Представление: $view");
}

// ----------------------------------------------------------------------
// 7. Тесты отчётов (прямые SQL)
// ----------------------------------------------------------------------
section("7. Тесты отчётов (SQL)");
runTest($pdo, "SELECT c.name, SUM(oi.shipped * p.price) as total_spent
                FROM client c
                JOIN `order` o ON c.id = o.client_link
                JOIN order_item oi ON o.id = oi.order_link
                JOIN product p ON oi.product_id = p.id
                WHERE o.shipment_date <= NOW()
                GROUP BY c.id
                ORDER BY total_spent DESC LIMIT 5", [], "Топ-5 клиентов по сумме заказов");
runTest($pdo, "SELECT p.id, p.name, p.min_level, COALESCE(SUM(ws.`stored`),0) as total_stored
                FROM product p
                LEFT JOIN warehouse_stock ws ON p.id = ws.product_link
                GROUP BY p.id
                HAVING total_stored < p.min_level", [], "Товары ниже минимального уровня");
runTest($pdo, "SELECT e.id, e.surname, e.name, COUNT(o.id) as orders_handled
                FROM employee e
                LEFT JOIN `order` o ON e.id = o.employee_id
                GROUP BY e.id", [], "Загруженность сотрудников");
runTest($pdo, "SELECT * FROM backorder_item", [], "Отложенные заказы");

// ----------------------------------------------------------------------
// 8. Тесты на ограничения и исключения
// ----------------------------------------------------------------------
section("8. Тесты на ограничения и исключения");
runTest($pdo, "DELETE FROM region WHERE id = 1", [], "Попытка удалить регион, на который ссылается клиент (должна быть ошибка RESTRICT)", true);
runTest($pdo, "INSERT INTO `order` (client_link, employee_id, sales_rep_number, order_date) VALUES (99999, 1, 1, NOW())", [], "Попытка вставить заказ с несуществующим client_link (ошибка целостности)", true);
runTest($pdo, "UPDATE warehouse_stock SET `stored` = -10 WHERE warehouse_link = $testWarehouseId AND product_link = $productId", [], "Попытка установить отрицательное количество (пока нет CHECK, но можно предотвратить триггером)");

// ----------------------------------------------------------------------
// 9. Комплексный тест «Сквозной бизнес-сценарий»
// ----------------------------------------------------------------------
section("9. Комплексный тест «Сквозной бизнес-сценарий»");
echo "<div style='border: 1px solid #ccc; padding: 15px; background: #fef9e6; margin-bottom: 20px;'>";
runTest($pdo, "INSERT INTO address (city, country) VALUES ('FullCity', 'FullCountry')", [], "Создаём адрес для клиента");
$addrId = $pdo->lastInsertId();
runTest($pdo, "INSERT INTO client (name, phone, balance, region_id, address_id) VALUES ('FullClient', '+999', 5000, 1, $addrId)", [], "Создаём клиента");
$fullClient = $pdo->lastInsertId();
runTest($pdo, "INSERT INTO product (name, price, min_level, max_level) VALUES ('FullProduct', 200, 10, 500)", [], "Создаём товар");
$fullProd = $pdo->lastInsertId();
runTest($pdo, "INSERT INTO warehouse_stock (warehouse_link, product_link, `stored`, `reserved`) VALUES ($testWarehouseId, $fullProd, 100, 0)", [], "Пополняем склад (регион клиента = 1)");
runTest($pdo, "CALL ДобавитьЗаказ($fullClient, $empId, $srNum, 'Безналичный', '".json_encode([["product_id"=>$fullProd,"quantity"=>80]])."', 'PARTIAL')", [], "Создаём заказ на 80 (достаточно)");
$orderFull = $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
runTest($pdo, "UPDATE `order` SET shipment_date = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE id = $orderFull", [], "Устанавливаем дату отгрузки = завтра");
runTest($pdo, "CALL SetVirtualTime(DATE_ADD(NOW(), INTERVAL 2 DAY))", [], "Перемещаем время на 2 дня вперёд (автоотгрузка)");
runTest($pdo, "SELECT shipped FROM order_item WHERE order_link = $orderFull", [], "Проверка: shipped должно стать 80");
runTest($pdo, "SELECT `stored`, `reserved` FROM warehouse_stock WHERE product_link = $fullProd", [], "Остатки: stored = 20, reserved = 0");
runTest($pdo, "CALL ДобавитьЗаказ($fullClient, $empId, $srNum, 'Безналичный', '".json_encode([["product_id"=>$fullProd,"quantity"=>50]])."', 'PARTIAL')", [], "Создаём второй заказ на 50 при остатке 20");
$secondOrder = $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
runTest($pdo, "SELECT * FROM reserved_stock WHERE order_link = $secondOrder AND product_link = $fullProd", [], "Резерв: 20 единиц");
runTest($pdo, "SELECT * FROM backorder_item WHERE order_link = $secondOrder AND product_id = $fullProd", [], "Backorder: 30 единиц");
runTest($pdo, "UPDATE warehouse_stock SET `stored` = `stored` + 40 WHERE product_link = $fullProd", [], "Пополняем склад на 40");
runTest($pdo, "CALL ProcessBackorders($fullProd)", [], "Обрабатываем отложенные заказы");
runTest($pdo, "SELECT quantity FROM reserved_stock WHERE order_link = $secondOrder AND product_link = $fullProd", [], "Резерв теперь должен стать 50");
runTest($pdo, "SELECT * FROM backorder_item WHERE order_link = $secondOrder AND product_id = $fullProd", [], "Backorder должен исчезнуть");
runTest($pdo, "UPDATE order_item SET shipped = 50 WHERE order_link = $secondOrder AND product_id = $fullProd", [], "Отгружаем полностью");
runTest($pdo, "SELECT `stored`, `reserved` FROM warehouse_stock WHERE product_link = $fullProd", [], "Остатки: stored уменьшилось на 50, reserved = 0");
runTest($pdo, "CALL ОтчётПоКлиенту($fullClient)", [], "Отчёт по новому клиенту");
echo "</div>";

// ----------------------------------------------------------------------
// Завершение
// ----------------------------------------------------------------------
echo "<hr><h3>✅ Все тесты выполнены. Подробности выше.</h3>";
echo "<p><a href='index.php'>← Вернуться на главную</a></p>";
echo "</body></html>";
?>