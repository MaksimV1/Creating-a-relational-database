DROP SCHEMA IF EXISTS `wholesale_company`;
CREATE SCHEMA `wholesale_company` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wholesale_company`;

SET FOREIGN_KEY_CHECKS = 0;
-- 2.1 Регионы
CREATE TABLE `region` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.2 Адреса
CREATE TABLE `address` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `city` VARCHAR(50) NOT NULL,
  `state` VARCHAR(50),
  `postcode` VARCHAR(20),
  `country` VARCHAR(50) NOT NULL,
  `district` VARCHAR(50),
  `street` VARCHAR(50),
  `house` INT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.3 Клиенты
CREATE TABLE `client` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20),
  `balance` DECIMAL(10,2) DEFAULT 0.00,
  `region_id` INT NOT NULL,
  `address_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_client_region_idx` (`region_id` ASC),
  INDEX `fk_client_address_idx` (`address_id` ASC),
  CONSTRAINT `fk_client_region`
    FOREIGN KEY (`region_id`) REFERENCES `region` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_client_address`
    FOREIGN KEY (`address_id`) REFERENCES `address` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.4 Склады
CREATE TABLE `warehouse` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20),
  `region_id` INT NOT NULL,
  `address_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_warehouse_region_idx` (`region_id` ASC),
  INDEX `fk_warehouse_address_idx` (`address_id` ASC),
  CONSTRAINT `fk_warehouse_region`
    FOREIGN KEY (`region_id`) REFERENCES `region` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_warehouse_address`
    FOREIGN KEY (`address_id`) REFERENCES `address` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.5 Отделы
CREATE TABLE `department` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `description` VARCHAR(500),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.6 Сотрудники
CREATE TABLE `employee` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `surname` VARCHAR(50) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `hire_date` DATE NOT NULL,
  `position` VARCHAR(50) NOT NULL,
  `salary` DECIMAL(10,2) NOT NULL,
  `department_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_employee_department_idx` (`department_id` ASC),
  CONSTRAINT `fk_employee_department`
    FOREIGN KEY (`department_id`) REFERENCES `department` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.7 Товары
CREATE TABLE `product` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `min_level` INT NOT NULL DEFAULT 0,
  `max_level` INT NOT NULL DEFAULT 1000,
  `photo_link` VARCHAR(250),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.8 Остатки товаров на складах
CREATE TABLE `warehouse_stock` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `warehouse_link` INT NOT NULL,
  `product_link` INT NOT NULL,
  `stored` INT NOT NULL DEFAULT 0,           -- количество на складе
  `reserved` INT NOT NULL DEFAULT 0,         -- зарезервировано под заказы
  `absence_reason` VARCHAR(500),             -- причина отсутствия
  `replenishment_date` DATE,                 -- дата пополнения
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_warehouse_product` (`warehouse_link`, `product_link`),
  INDEX `fk_ws_warehouse_idx` (`warehouse_link` ASC),
  INDEX `fk_ws_product_idx` (`product_link` ASC),
  CONSTRAINT `fk_ws_warehouse`
    FOREIGN KEY (`warehouse_link`) REFERENCES `warehouse` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ws_product`
    FOREIGN KEY (`product_link`) REFERENCES `product` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.9 Торговые представители (связь клиент-сотрудник)
CREATE TABLE `sales_rep` (
  `number` INT NOT NULL AUTO_INCREMENT,
  `client_link` INT NOT NULL,
  `employee_link` INT NOT NULL,
  `commission_fees` DECIMAL(5,2) DEFAULT 0.00,
  `username` VARCHAR(50) NOT NULL,
  `work_start_date` DATE NOT NULL,
  `remarks` VARCHAR(500),
  PRIMARY KEY (`number`),
  INDEX `fk_sr_client_idx` (`client_link` ASC),
  INDEX `fk_sr_employee_idx` (`employee_link` ASC),
  CONSTRAINT `fk_sr_client`
    FOREIGN KEY (`client_link`) REFERENCES `client` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_sr_employee`
    FOREIGN KEY (`employee_link`) REFERENCES `employee` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.10 Платёжные профили клиентов
CREATE TABLE `payment_profile` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `client_link` INT NOT NULL,
  `payment_requests` VARCHAR(500),
  `recipient_address` INT NOT NULL,   -- ссылка на адрес грузополучателя
  `sales_rep_link` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_pp_client_idx` (`client_link` ASC),
  INDEX `fk_pp_address_idx` (`recipient_address` ASC),
  INDEX `fk_pp_salesrep_idx` (`sales_rep_link` ASC),
  CONSTRAINT `fk_pp_client`
    FOREIGN KEY (`client_link`) REFERENCES `client` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_pp_address`
    FOREIGN KEY (`recipient_address`) REFERENCES `address` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_pp_salesrep`
    FOREIGN KEY (`sales_rep_link`) REFERENCES `sales_rep` (`number`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.11 Заказы
CREATE TABLE `order` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `client_link` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `sales_rep_number` INT NOT NULL,
  `order_date` DATETIME NOT NULL,
  `shipment_date` DATETIME,
  `payment_method` VARCHAR(50),
  PRIMARY KEY (`id`),
  INDEX `fk_order_client_idx` (`client_link` ASC),
  INDEX `fk_order_employee_idx` (`employee_id` ASC),
  INDEX `fk_order_salesrep_idx` (`sales_rep_number` ASC),
  CONSTRAINT `fk_order_client`
    FOREIGN KEY (`client_link`) REFERENCES `client` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_order_employee`
    FOREIGN KEY (`employee_id`) REFERENCES `employee` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_order_salesrep`
    FOREIGN KEY (`sales_rep_number`) REFERENCES `sales_rep` (`number`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.12 Позиции заказа
CREATE TABLE `order_item` (
  `order_link` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `shipped` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`order_link`, `product_id`),
  INDEX `fk_oi_order_idx` (`order_link` ASC),
  INDEX `fk_oi_product_idx` (`product_id` ASC),
  CONSTRAINT `fk_oi_order`
    FOREIGN KEY (`order_link`) REFERENCES `order` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product`
    FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- НОВЫЕ ТАБЛИЦЫ ДЛЯ КОРРЕКТНОГО РЕЗЕРВИРОВАНИЯ И ОЖИДАНИЯ
-- ============================================================

CREATE TABLE `reserved_stock` (
  `order_link` INT NOT NULL,
  `warehouse_link` INT NOT NULL,
  `product_link` INT NOT NULL,
  `quantity` INT NOT NULL,
  PRIMARY KEY (`order_link`, `warehouse_link`, `product_link`),
  INDEX `fk_rs_warehouse` (`warehouse_link`),
  INDEX `fk_rs_product` (`product_link`),
  CONSTRAINT `fk_rs_order` FOREIGN KEY (`order_link`) REFERENCES `order` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rs_warehouse` FOREIGN KEY (`warehouse_link`) REFERENCES `warehouse` (`id`),
  CONSTRAINT `fk_rs_product` FOREIGN KEY (`product_link`) REFERENCES `product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `backorder_item` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_link` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT NOW(),
  PRIMARY KEY (`id`),
  INDEX `fk_backorder_order` (`order_link`),
  INDEX `fk_backorder_product` (`product_id`),
  CONSTRAINT `fk_backorder_order` FOREIGN KEY (`order_link`) REFERENCES `order` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_backorder_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `virtual_time` (
    `id` INT NOT NULL DEFAULT 1,
    `current_time` DATETIME NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `order_item`;
TRUNCATE TABLE `order`;
TRUNCATE TABLE `payment_profile`;
TRUNCATE TABLE `sales_rep`;
TRUNCATE TABLE `warehouse_stock`;
TRUNCATE TABLE `employee`;
TRUNCATE TABLE `client`;
TRUNCATE TABLE `warehouse`;
TRUNCATE TABLE `product`;
TRUNCATE TABLE `department`;
TRUNCATE TABLE `address`;
TRUNCATE TABLE `region`;

SET FOREIGN_KEY_CHECKS = 1;

-- 3.1 Регионы (6 записей)
INSERT INTO `region` (`name`) VALUES
('Северная Америка'),
('Южная Америка'),
('Африка'),
('Средний Восток'),
('Азия'),
('Европа');

-- 3.2 Адреса 
INSERT INTO `address` (`city`, `state`, `postcode`, `country`, `district`, `street`, `house`) VALUES
('Сан-Франциско', 'Калифорния', '94105', 'США', 'Financial District', 'Market Street', 101),
('Сиэттл', 'Вашингтон', '98101', 'США', 'Downtown', 'Pike Street', 1200),
('Нью-Йорк', 'Нью-Йорк', '10001', 'США', 'Manhattan', '5th Avenue', 350),
('Лос-Анджелес', 'Калифорния', '90001', 'США', 'Downtown', 'Figueroa Street', 777),
('Чикаго', 'Иллинойс', '60601', 'США', 'Loop', 'Michigan Avenue', 500),
('Майами', 'Флорида', '33101', 'США', 'Downtown', 'Biscayne Blvd', 200),
('Бостон', 'Массачусетс', '02108', 'США', 'Beacon Hill', 'Beacon Street', 15),
('Даллас', 'Техас', '75201', 'США', 'Uptown', 'Main Street', 212),
('Филадельфия', 'Пенсильвания', '19102', 'США', 'Center City', 'Market Street', 1000),
('Атланта', 'Джорджия', '30303', 'США', 'Downtown', 'Peachtree Street', 303),
('Денвер', 'Колорадо', '80202', 'США', 'LoDo', 'Larimer Street', 1600),
('Финикс', 'Аризона', '85001', 'США', 'Downtown', 'Washington Street', 200),
('Детройт', 'Мичиган', '48226', 'США', 'Downtown', 'Woodward Avenue', 500),
('Портленд', 'Орегон', '97201', 'США', 'Downtown', 'Broadway', 600),
('Лас-Вегас', 'Невада', '89101', 'США', 'Downtown', 'Fremont Street', 425),
('Торонто', 'Онтарио', 'M5V 2T6', 'Канада', 'Downtown', 'Front Street', 1),      
('Сан-Паулу', 'Сан-Паулу', '01000-000', 'Бразилия', 'Centro', 'Paulista Avenue', 1000), 
('Йоханнесбург', 'Гаутенг', '2000', 'ЮАР', 'CBD', 'Main Street', 1),            
('Дубай', 'Дубай', '00000', 'ОАЭ', 'Trade Centre', 'Sheikh Zayed Road', 1),    
('Шанхай', 'Шанхай', '200000', 'Китай', 'Pudong', 'Century Avenue', 1),        
('Лондон', 'Англия', 'EC1A 1BB', 'Великобритания', 'City', 'New Street', 1);  

-- 3.3 Склады
INSERT INTO `warehouse` (`name`, `phone`, `region_id`, `address_id`) VALUES
('Склад Северная Америка', '+1-555-1001', 1, 16),
('Склад Южная Америка', '+1-555-1002', 2, 17),
('Склад Африка', '+1-555-1003', 3, 18),
('Склад Средний Восток', '+1-555-1004', 4, 19),
('Склад Азия', '+1-555-1005', 5, 20),
('Склад Европа', '+1-555-1006', 6, 21);

-- 3.4 Клиенты 
INSERT INTO `client` (`name`, `phone`, `balance`, `region_id`, `address_id`) VALUES
('Big John\'s Sports Emporium', '+1-415-555-1234', 25000.00, 1, 1),
('Womansport', '+1-206-555-6789', 18000.00, 1, 2),
('Sports World NYC', '+1-212-555-1111', 5000.00, 1, 3),
('LA Fitness Gear', '+1-213-555-2222', 7500.00, 1, 4),
('Chicago Sports Store', '+1-312-555-3333', 3200.00, 1, 5),
('Miami Beach Sports', '+1-305-555-4444', 9400.00, 1, 6),
('Boston Athletic', '+1-617-555-5555', 6100.00, 1, 7),
('Dallas Sport Supply', '+1-214-555-6666', 8700.00, 1, 8),
('Philly Sports', '+1-215-555-7777', 4300.00, 1, 9),
('Atlanta Fitness', '+1-404-555-8888', 11200.00, 1, 10),
('Denver Outdoor', '+1-303-555-9999', 2500.00, 1, 11),
('Phoenix Sun Sports', '+1-602-555-0000', 6800.00, 1, 12),
('Detroit Athletic', '+1-313-555-1212', 3900.00, 1, 13),
('Portland Running Co', '+1-503-555-3434', 4700.00, 1, 14),
('Vegas Sports Hub', '+1-702-555-5656', 10200.00, 1, 15);

-- 3.5 Отделы
INSERT INTO `department` (`name`, `description`) VALUES
('Отдел продаж', 'Прием и обработка заказов'),
('Логистика', 'Управление складом и доставкой'),
('Бухгалтерия', 'Финансы и платежные профили'),
('Маркетинг', 'Реклама и продвижение'),
('IT-отдел', 'Поддержка информационных систем'),
('HR', 'Управление персоналом');

-- 3.6 Сотрудники
DELIMITER $$
CREATE PROCEDURE generate_employees()
BEGIN
  DECLARE i INT DEFAULT 1;
  DECLARE dept_id INT;
  DECLARE first_names VARCHAR(200) DEFAULT 'Иван,Петр,Сидор,Алексей,Дмитрий,Михаил,Николай,Андрей,Сергей,Владимир,Анна,Мария,Елена,Ольга,Татьяна,Светлана,Наталья,Юлия,Ирина,Екатерина';
  DECLARE last_names VARCHAR(200) DEFAULT 'Иванов,Петров,Сидоров,Алексеев,Дмитриев,Михайлов,Николаев,Андреев,Сергеев,Владимиров,Антонова,Морозова,Волкова,Кузнецова,Соколова,Лебедева,Новикова,Козлова,Павлова,Васильева';
  DECLARE positions VARCHAR(200) DEFAULT 'Менеджер,Кладовщик,Бухгалтер,Аналитик,Программист,HR-специалист,Старший продавец,Логист,Финансист,Директор';
  DECLARE fname, lname, pos VARCHAR(50);
  DECLARE sal DECIMAL(10,2);
  DECLARE hire DATE;
  
  WHILE i <= 120 DO
    SET dept_id = 1 + FLOOR(RAND() * 6);
    SET lname = ELT(1 + FLOOR(RAND() * 20), 'Иванов','Петров','Сидоров','Алексеев','Дмитриев','Михайлов','Николаев','Андреев','Сергеев','Владимиров','Антонова','Морозова','Волкова','Кузнецова','Соколова','Лебедева','Новикова','Козлова','Павлова','Васильева');
    SET fname = ELT(1 + FLOOR(RAND() * 20), 'Иван','Петр','Сидор','Алексей','Дмитрий','Михаил','Николай','Андрей','Сергей','Владимир','Анна','Мария','Елена','Ольга','Татьяна','Светлана','Наталья','Юлия','Ирина','Екатерина');
    SET pos = ELT(1 + FLOOR(RAND() * 10), 'Менеджер','Кладовщик','Бухгалтер','Аналитик','Программист','HR-специалист','Старший продавец','Логист','Финансист','Директор');
    SET sal = 40000 + FLOOR(RAND() * 60000);
    SET hire = DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 3650) DAY);
    
    INSERT INTO `employee` (`surname`, `name`, `hire_date`, `position`, `salary`, `department_id`)
    VALUES (lname, fname, hire, pos, sal, dept_id);
    
    SET i = i + 1;
  END WHILE;
END$$
DELIMITER ;
CALL generate_employees();

-- 3.7 Товары
INSERT INTO `product` (`name`, `description`, `price`, `min_level`, `max_level`, `photo_link`) VALUES
('Футбольный мяч', 'Мяч FIFA Quality Pro, размер 5', 2500.00, 10, 500, 'http://example.com/soccer_ball.jpg'),
('Баскетбольный мяч', 'Резиновый, размер 7', 1800.00, 8, 400, 'http://example.com/basketball.jpg'),
('Теннисная ракетка', 'Профессиональная, вес 300г', 4500.00, 5, 200, 'http://example.com/racket.jpg'),
('Беговые кроссовки', 'Мужские, размер 42-46', 6000.00, 20, 800, 'http://example.com/running_shoes.jpg'),
('Гантели 10кг', 'Чугунные, пара', 3500.00, 15, 600, 'http://example.com/dumbbell.jpg'),
('Велотренажер', 'Магнитный, с компьютером', 25000.00, 2, 50, 'http://example.com/exercise_bike.jpg'),
('Плавательные очки', 'Антизапотевание, UV защита', 800.00, 30, 1000, 'http://example.com/swim_goggles.jpg'),
('Фитнес-коврик', 'Толщина 10мм, нескользящий', 1200.00, 25, 700, 'http://example.com/yoga_mat.jpg'),
('Хоккейная клюшка', 'Композитная, левая стойка', 3200.00, 10, 300, 'http://example.com/hockey_stick.jpg'),
('Волейбольный мяч', 'Микрофибра, официальный', 2200.00, 10, 400, 'http://example.com/volleyball.jpg'),
('Лыжи горные', 'Комплект с креплениями', 18000.00, 3, 100, 'http://example.com/skis.jpg'),
('Сноуборд', 'Кембер, 155см', 15000.00, 5, 120, 'http://example.com/snowboard.jpg'),
('Боксерские перчатки', 'Кожаные, 12 унций', 4000.00, 8, 250, 'http://example.com/boxing_gloves.jpg'),
('Шлем велосипедный', 'Легкий, вентиляция', 2900.00, 12, 350, 'http://example.com/helmet.jpg'),
('Эспандер', 'Резиновый, 5 уровней', 500.00, 40, 1500, 'http://example.com/expander.jpg'),
('Спортивная форма', 'Футболка+шорты, полиэстер', 1500.00, 30, 1000, 'http://example.com/uniform.jpg'),
('Рюкзак для спорта', 'Вместительный, 40л', 2500.00, 15, 500, 'http://example.com/backpack.jpg'),
('Скакалка', 'Регулируемая длина', 350.00, 50, 2000, 'http://example.com/jump_rope.jpg'),
('Мат для единоборств', 'Толщина 5см', 8500.00, 2, 80, 'http://example.com/martial_mat.jpg'),
('Тренажер для пресса', 'Регулируемый', 7000.00, 5, 150, 'http://example.com/ab_bench.jpg');

-- 3.8 Остатки товаров на складах
INSERT IGNORE INTO `warehouse_stock` (`warehouse_link`, `product_link`, `stored`, `reserved`, `replenishment_date`)
SELECT w.id, p.id, 
       FLOOR(5000 + RAND() * 500),
       FLOOR(RAND() * 50),
       DATE_ADD(CURDATE(), INTERVAL FLOOR(RAND() * 30) DAY)
FROM `warehouse` w
CROSS JOIN `product` p;

-- 3.9 Торговые представители
INSERT INTO `sales_rep` (`client_link`, `employee_link`, `commission_fees`, `username`, `work_start_date`, `remarks`)
SELECT 
  c.id,
  (SELECT id FROM `employee` ORDER BY RAND() LIMIT 1) AS emp_id,
  ROUND(2 + RAND() * 8, 2) AS comm,
  CONCAT('rep_', c.id),
  DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 1000) DAY),
  'Автоматически назначен'
FROM `client` c
LIMIT 15;

INSERT INTO `sales_rep` (`client_link`, `employee_link`, `commission_fees`, `username`, `work_start_date`, `remarks`)
SELECT 
  c.id,
  (SELECT id FROM `employee` WHERE id NOT IN (SELECT employee_link FROM `sales_rep` WHERE client_link = c.id) ORDER BY RAND() LIMIT 1),
  ROUND(3 + RAND() * 7, 2),
  CONCAT('rep2_', c.id),
  DATE_SUB(CURDATE(), INTERVAL FLOOR(RAND() * 800) DAY),
  'Дополнительный представитель'
FROM `client` c
WHERE RAND() < 0.5; 

-- 3.10 Платёжные профили
INSERT INTO `payment_profile` (`client_link`, `payment_requests`, `recipient_address`, `sales_rep_link`)
SELECT 
  c.id,
  concat('Счет № ', LPAD(FLOOR(RAND() * 10000000000000000000), 20, '0')),
  c.address_id,
  (SELECT `number` FROM `sales_rep` WHERE client_link = c.id LIMIT 1)
FROM `client` c;

DELIMITER $$
CREATE PROCEDURE `generate_orders`()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE client_id INT;
  DECLARE orders_per_client INT;
  DECLARE order_id INT;
  DECLARE items_count INT;
  DECLARE cur CURSOR FOR SELECT id FROM `client`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
  
  OPEN cur;
  read_loop: LOOP
    FETCH cur INTO client_id;
    IF done THEN LEAVE read_loop; END IF;
    
    SET orders_per_client = 5 + FLOOR(RAND() * 16);
    
    WHILE orders_per_client > 0 DO
      INSERT INTO `order` (`client_link`, `employee_id`, `sales_rep_number`, `order_date`, `shipment_date`, `payment_method`)
      VALUES (
        client_id,
        (SELECT employee_link FROM `sales_rep` WHERE client_link = client_id LIMIT 1),
        (SELECT `number` FROM `sales_rep` WHERE client_link = client_id LIMIT 1),
        DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 365) DAY),
        CASE WHEN RAND() < 0.7 THEN DATE_ADD(NOW(), INTERVAL FLOOR(RAND() * 14) DAY) ELSE NULL END,
        ELT(1 + FLOOR(RAND() * 3), 'Безналичный', 'Наличный', 'Кредитная карта')
      );
      
      SET order_id = LAST_INSERT_ID();
      SET items_count = 1 + FLOOR(RAND() * 20);
      
      DROP TEMPORARY TABLE IF EXISTS temp_products;
      CREATE TEMPORARY TABLE temp_products AS
        SELECT id AS product_id, FLOOR(1 + RAND() * 50) AS qty, FLOOR(RAND() * (FLOOR(1 + RAND() * 50))) AS shipped
        FROM product
        ORDER BY RAND()
        LIMIT items_count;
      
      INSERT INTO order_item (order_link, product_id, quantity, shipped)
      SELECT order_id, product_id, qty, shipped FROM temp_products;
      
      SET orders_per_client = orders_per_client - 1;
    END WHILE;
  END LOOP;
  CLOSE cur;
END$$
DELIMITER ;

DELIMITER $$
CREATE FUNCTION GetOrderTotal(order_id INT) RETURNS DECIMAL(10,2) READS SQL DATA
BEGIN
  DECLARE total DECIMAL(10,2);
  SELECT SUM(oi.quantity * p.price) INTO total
  FROM order_item oi
  JOIN product p ON oi.product_id = p.id
  WHERE oi.order_link = order_id;
  RETURN IFNULL(total, 0);
END$$
DELIMITER ;

INSERT INTO `virtual_time` (`id`, `current_time`) 
VALUES (1, NOW())
ON DUPLICATE KEY UPDATE `current_time` = NOW();

CREATE OR REPLACE VIEW `Клиент` AS 
SELECT 
    c.id AS `Идентификатор`,
    c.name AS `Название`,
    c.phone AS `Телефон`,
    c.balance AS `Средства на счете`,
    r.name AS `Регион`,
    a.city AS `Город`,
    a.state AS `Штат`,
    a.postcode AS `Почтовый индекс`,
    a.country AS `Страна`,
    a.district AS `Район`,
    a.street AS `Улица`,
    a.house AS `Дом`
FROM wholesale_company.client c
JOIN wholesale_company.region r ON c.region_id = r.id
JOIN wholesale_company.address a ON c.address_id = a.id;

CREATE OR REPLACE VIEW `Товар` AS
SELECT 
    id AS `Идентификатор`,
    name AS `Название`,
    description AS `Описание`,
    price AS `Цена`,
    min_level AS `Минимальный уровень`,
    max_level AS `Максимальный уровень`,
    photo_link AS `Ссылка на фото`
FROM product;

CREATE OR REPLACE VIEW `Склад` AS
SELECT 
    w.id AS `Идентификатор`,
    w.name AS `Название`,
    w.phone AS `Телефон`,
    r.name AS `Регион`,
    a.city AS `Город`,
    a.street AS `Улица`,
    a.house AS `Дом`
FROM warehouse w
JOIN region r ON w.region_id = r.id
JOIN address a ON w.address_id = a.id;

CREATE OR REPLACE VIEW `Количество товара на складе` AS
SELECT 
    ws.id AS `Идентификатор`,
    p.name AS `Товар`,
    wh.name AS `Склад`,
    ws.stored AS `Хранится`,
    ws.reserved AS `Зарезервировано`,
    (ws.stored - ws.reserved) AS `Доступно`,
    ws.replenishment_date AS `Дата пополнения`,
    ws.absence_reason AS `Причина отсутствия`
FROM warehouse_stock ws
JOIN product p ON ws.product_link = p.id
JOIN warehouse wh ON ws.warehouse_link = wh.id;

CREATE OR REPLACE VIEW `Заказ` AS
SELECT 
    o.id AS `Идентификатор`,
    o.order_date AS `Дата заказа`,
    o.shipment_date AS `Дата отгрузки`,
    o.payment_method AS `Способ оплаты`,
    c.name AS `Клиент`,
    e.name AS `Сотрудник`,
    e.surname AS `Фамилия сотрудника`
FROM `order` o
JOIN client c ON o.client_link = c.id
JOIN employee e ON o.employee_id = e.id;

CREATE OR REPLACE VIEW `Позиции заказа` AS
SELECT 
    oi.order_link AS `Ссылка на заказ`,
    c.name AS `Клиент`,
    p.name AS `Товар`,
    oi.quantity AS `Количество`,
    oi.shipped AS `Отгружено`,
    (oi.quantity - oi.shipped) AS `Не отгружено`
FROM order_item oi
JOIN `order` o ON oi.order_link = o.id
JOIN client c ON o.client_link = c.id
JOIN product p ON oi.product_id = p.id;

CREATE OR REPLACE VIEW `Сотрудник` AS
SELECT 
    e.id AS `Идентификатор`,
    e.name AS `Имя`,
    e.surname AS `Фамилия`,
    e.position AS `Должность`,
    e.salary AS `Зарплата`,
    e.hire_date AS `Дата найма`,
    d.name AS `Отдел`
FROM employee e
JOIN department d ON e.department_id = d.id;

CREATE OR REPLACE VIEW `Отдел` AS
SELECT 
    id AS `Идентификатор`,
    name AS `Название`,
    description AS `Описание`
FROM department;

CREATE OR REPLACE VIEW `Торговый представитель` AS
SELECT 
    s.number AS `Номер`,
    e.name AS `Имя представителя`,
    e.surname AS `Фамилия`,
    s.commission_fees AS `Комиссионные`,
    s.username AS `Имя пользователя`,
    s.work_start_date AS `Дата начала работ`,
    s.remarks AS `Замечания`,
    c.name AS `Обслуживаемый клиент`
FROM sales_rep s
JOIN employee e ON s.employee_link = e.id
JOIN client c ON s.client_link = c.id;

CREATE OR REPLACE VIEW `Платёжный профиль` AS
SELECT 
    pp.id AS `Идентификатор`,
    c.name AS `Клиент`,
    pp.payment_requests AS `Платёжные реквизиты`,
    a.city AS `Город`,
    a.state AS `Штат`,
    a.postcode AS `Почтовый индекс`,
    a.country AS `Страна`,
    a.district AS `Район`,
    a.street AS `Улица`,
    a.house AS `Дом`
FROM payment_profile pp
JOIN client c ON pp.client_link = c.id
JOIN address a ON pp.recipient_address = a.id;

CREATE OR REPLACE VIEW `Регион` AS
SELECT 
    id AS `Идентификатор`,
    name AS `Название`
FROM region;

CREATE OR REPLACE VIEW `Адрес` AS
SELECT 
    id AS `Идентификатор`,
    city AS `Город`,
    state AS `Штат`,
    postcode AS `Почтовый индекс`,
    country AS `Страна`,
    district AS `Район`,
    street AS `Улица`,
    house AS `Дом`
FROM address;

DELIMITER $$
CREATE PROCEDURE `ДобавитьЗаказ`(
    IN p_client_id INT,
    IN p_employee_id INT,
    IN p_sales_rep_number INT,
    IN p_payment_method VARCHAR(50),
    IN p_items_json JSON,
    IN p_shortage_action ENUM('CANCEL', 'PARTIAL', 'WAIT')
)
BEGIN
    DECLARE v_order_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_available_total INT;
    DECLARE v_available_by_region INT;
    DECLARE v_client_region INT;
    DECLARE v_remaining INT;
    DECLARE v_warehouse_id INT;
    DECLARE v_reserved_qty INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Получаем регион клиента
    SELECT region_id INTO v_client_region FROM client WHERE id = p_client_id;
    
    -- Создаём заказ
    INSERT INTO `order` (`client_link`, `employee_id`, `sales_rep_number`, `order_date`, `payment_method`)
    VALUES (p_client_id, p_employee_id, p_sales_rep_number, NOW(), p_payment_method);
    
    SET v_order_id = LAST_INSERT_ID();
    
    WHILE i < JSON_LENGTH(p_items_json) DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_items_json, CONCAT('$[', i, '].product_id')));
        SET v_quantity = JSON_UNQUOTE(JSON_EXTRACT(p_items_json, CONCAT('$[', i, '].quantity')));
        
        SELECT SUM(`stored` - `reserved`) INTO v_available_total
		FROM `warehouse_stock`
		WHERE `product_link` = v_product_id;
        
        -- Доступное количество в складах того же региона, что и клиент
        SELECT SUM(ws.stored - ws.reserved) INTO v_available_by_region
        FROM warehouse_stock ws
        JOIN warehouse w ON ws.warehouse_link = w.id
        WHERE ws.product_link = v_product_id AND w.region_id = v_client_region;
        
        IF v_available_total < v_quantity THEN
            IF p_shortage_action = 'CANCEL' THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Недостаточно товара на складах, заказ отменён';
            ELSEIF p_shortage_action = 'WAIT' THEN
                -- Весь заказ в ожидание
                INSERT INTO backorder_item (order_link, product_id, quantity)
                VALUES (v_order_id, v_product_id, v_quantity);
                INSERT INTO order_item (order_link, product_id, quantity, shipped)
                VALUES (v_order_id, v_product_id, v_quantity, 0);
            ELSE  -- PARTIAL
                SET v_remaining = v_quantity;
                -- Сначала резервируем в регионе клиента
                BLOCK_REGION: BEGIN
                    DECLARE done INT DEFAULT FALSE;
                    DECLARE cur CURSOR FOR 
                        SELECT ws.warehouse_link, (ws.stored - ws.reserved) as avail
                        FROM warehouse_stock ws
                        JOIN warehouse w ON ws.warehouse_link = w.id
                        WHERE ws.product_link = v_product_id AND w.region_id = v_client_region
                          AND (ws.stored - ws.reserved) > 0
                        ORDER BY avail DESC;
                    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
                    OPEN cur;
                    read_loop_region: LOOP
                        FETCH cur INTO v_warehouse_id, v_available_by_region;
                        IF done THEN LEAVE read_loop_region; END IF;
                        IF v_remaining <= 0 THEN LEAVE read_loop_region; END IF;
                        SET v_reserved_qty = LEAST(v_remaining, v_available_by_region);
                        INSERT INTO reserved_stock (order_link, warehouse_link, product_link, quantity)
                        VALUES (v_order_id, v_warehouse_id, v_product_id, v_reserved_qty)
                        ON DUPLICATE KEY UPDATE quantity = quantity + v_reserved_qty;
                        UPDATE warehouse_stock
                        SET reserved = reserved + v_reserved_qty
                        WHERE warehouse_link = v_warehouse_id AND product_link = v_product_id;
                        SET v_remaining = v_remaining - v_reserved_qty;
                    END LOOP;
                    CLOSE cur;
                END;
                -- Затем резервируем на других складах
                IF v_remaining > 0 THEN
                    BLOCK_OTHER: BEGIN
                        DECLARE done2 INT DEFAULT FALSE;
                        DECLARE cur2 CURSOR FOR 
                            SELECT ws.warehouse_link, (ws.stored - ws.reserved) as avail
                            FROM warehouse_stock ws
                            WHERE ws.product_link = v_product_id AND (ws.stored - ws.reserved) > 0
                            ORDER BY avail DESC;
                        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done2 = TRUE;
                        OPEN cur2;
                        read_loop_other: LOOP
                            FETCH cur2 INTO v_warehouse_id, v_available_by_region;
                            IF done2 THEN LEAVE read_loop_other; END IF;
                            IF v_remaining <= 0 THEN LEAVE read_loop_other; END IF;
                            SET v_reserved_qty = LEAST(v_remaining, v_available_by_region);
                            INSERT INTO reserved_stock (order_link, warehouse_link, product_link, quantity)
                            VALUES (v_order_id, v_warehouse_id, v_product_id, v_reserved_qty)
                            ON DUPLICATE KEY UPDATE quantity = quantity + v_reserved_qty;
                            UPDATE warehouse_stock
                            SET reserved = reserved + v_reserved_qty
                            WHERE warehouse_link = v_warehouse_id AND product_link = v_product_id;
                            SET v_remaining = v_remaining - v_reserved_qty;
                        END LOOP;
                        CLOSE cur2;
                    END;
                END IF;
                -- Если всё равно не хватило, остаток в backorder
                IF v_remaining > 0 THEN
                    INSERT INTO backorder_item (order_link, product_id, quantity)
                    VALUES (v_order_id, v_product_id, v_remaining);
                END IF;
                INSERT INTO order_item (order_link, product_id, quantity, shipped)
                VALUES (v_order_id, v_product_id, v_quantity, 0);
            END IF;
        ELSE
            -- Достаточно товара – резервируем полностью
            SET v_remaining = v_quantity;
            -- Сначала резерв в регионе клиента
            BLOCK_FULL_REGION: BEGIN
                DECLARE done3 INT DEFAULT FALSE;
                DECLARE cur3 CURSOR FOR 
                    SELECT ws.warehouse_link, (ws.stored - ws.reserved) as avail
                    FROM warehouse_stock ws
                    JOIN warehouse w ON ws.warehouse_link = w.id
                    WHERE ws.product_link = v_product_id AND w.region_id = v_client_region
                      AND (ws.stored - ws.reserved) > 0
                    ORDER BY avail DESC;
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET done3 = TRUE;
                OPEN cur3;
                read_loop_full_reg: LOOP
                    FETCH cur3 INTO v_warehouse_id, v_available_by_region;
                    IF done3 THEN LEAVE read_loop_full_reg; END IF;
                    IF v_remaining <= 0 THEN LEAVE read_loop_full_reg; END IF;
                    SET v_reserved_qty = LEAST(v_remaining, v_available_by_region);
                    INSERT INTO reserved_stock (order_link, warehouse_link, product_link, quantity)
                    VALUES (v_order_id, v_warehouse_id, v_product_id, v_reserved_qty)
                    ON DUPLICATE KEY UPDATE quantity = quantity + v_reserved_qty;
                    UPDATE warehouse_stock
                    SET reserved = reserved + v_reserved_qty
                    WHERE warehouse_link = v_warehouse_id AND product_link = v_product_id;
                    SET v_remaining = v_remaining - v_reserved_qty;
                END LOOP;
                CLOSE cur3;
            END;
            -- Затем на других складах
            IF v_remaining > 0 THEN
                BLOCK_FULL_OTHER: BEGIN
                    DECLARE done4 INT DEFAULT FALSE;
                    DECLARE cur4 CURSOR FOR 
                        SELECT ws.warehouse_link, (ws.stored - ws.reserved) as avail
                        FROM warehouse_stock ws
                        WHERE ws.product_link = v_product_id AND (ws.stored - ws.reserved) > 0
                        ORDER BY avail DESC;
                    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done4 = TRUE;
                    OPEN cur4;
                    read_loop_full_other: LOOP
                        FETCH cur4 INTO v_warehouse_id, v_available_by_region;
                        IF done4 THEN LEAVE read_loop_full_other; END IF;
                        IF v_remaining <= 0 THEN LEAVE read_loop_full_other; END IF;
                        SET v_reserved_qty = LEAST(v_remaining, v_available_by_region);
                        INSERT INTO reserved_stock (order_link, warehouse_link, product_link, quantity)
                        VALUES (v_order_id, v_warehouse_id, v_product_id, v_reserved_qty)
                        ON DUPLICATE KEY UPDATE quantity = quantity + v_reserved_qty;
                        UPDATE warehouse_stock
                        SET reserved = reserved + v_reserved_qty
                        WHERE warehouse_link = v_warehouse_id AND product_link = v_product_id;
                        SET v_remaining = v_remaining - v_reserved_qty;
                    END LOOP;
                    CLOSE cur4;
                END;
            END IF;
            INSERT INTO order_item (order_link, product_id, quantity, shipped)
            VALUES (v_order_id, v_product_id, v_quantity, 0);
        END IF;
        
        SET i = i + 1;
    END WHILE;
    
    COMMIT;
    SELECT v_order_id AS `Номер нового заказа`;
END$$

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE `ProcessBackorders`(IN p_product_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_order_id INT;
    DECLARE v_qty_needed INT;
    DECLARE v_available INT;
    DECLARE v_client_region INT;
    
    DECLARE cur CURSOR FOR 
        SELECT order_link, quantity FROM backorder_item WHERE product_id = p_product_id ORDER BY created_at;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_order_id, v_qty_needed;
        IF done THEN LEAVE read_loop; END IF;
        
        SELECT c.region_id INTO v_client_region 
        FROM `order` o JOIN client c ON o.client_link = c.id WHERE o.id = v_order_id;
        
        SELECT SUM(`stored` - `reserved`) INTO v_available 
        FROM warehouse_stock WHERE product_link = p_product_id;
        
        IF v_available >= v_qty_needed THEN
            -- Здесь должна быть логика резервирования, аналогичная той, что в ДобавитьЗаказ.
            -- Для полноты можно вынести резервирование в отдельную процедуру.
            -- В данном примере просто удаляем запись (требует доработки).
            DELETE FROM backorder_item WHERE order_link = v_order_id AND product_id = p_product_id;
        END IF;
    END LOOP;
    CLOSE cur;
END$$

DELIMITER ;

DELIMITER $$
CREATE PROCEDURE `ОтчётПоКлиенту`(IN p_client_id INT)
BEGIN
    SELECT 
        c.name AS `Клиент`,
        c.balance AS `Баланс`,
        o.id AS `Заказ`,
        o.order_date AS `Дата`,
        SUM(oi.quantity * pr.price) AS `Сумма`
    FROM client c
    JOIN `order` o ON c.id = o.client_link
    JOIN order_item oi ON o.id = oi.order_link
    JOIN product pr ON oi.product_id = pr.id
    WHERE c.id = p_client_id
    GROUP BY o.id
    ORDER BY o.order_date DESC;
END$$
DELIMITER ;

DELIMITER $$
CREATE FUNCTION `ЕстьЗадолженность`(p_client_id INT) RETURNS BOOLEAN DETERMINISTIC
BEGIN
    DECLARE debt DECIMAL(10,2);
    SELECT balance INTO debt FROM client WHERE id = p_client_id;
    RETURN debt < 0;
END$$
DELIMITER ;

DROP FUNCTION IF EXISTS `GetVirtualTime`;
DELIMITER $$
CREATE FUNCTION `GetVirtualTime`() RETURNS DATETIME DETERMINISTIC
BEGIN
    DECLARE vt DATETIME;
    SELECT `current_time` INTO vt FROM `virtual_time` WHERE `id` = 1;
    RETURN vt;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS `SetVirtualTime`;
DELIMITER $$
CREATE PROCEDURE `SetVirtualTime`(IN `new_time` DATETIME)
BEGIN
    DECLARE `done` INT DEFAULT FALSE;
    DECLARE `order_id` INT;
    DECLARE `cur` CURSOR FOR 
        SELECT DISTINCT o.`id`
        FROM `order` o
        JOIN `order_item` oi ON o.`id` = oi.`order_link`
        WHERE o.`shipment_date` IS NOT NULL 
          AND o.`shipment_date` <= `new_time`
          AND oi.`shipped` < oi.`quantity`;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET `done` = TRUE;
    
    START TRANSACTION;
    
    -- Обновляем виртуальное время
    UPDATE `virtual_time` SET `current_time` = `new_time` WHERE `id` = 1;
    
    -- Отгружаем все заказы, у которых дата отгрузки <= new_time и не полностью отгружены
    OPEN `cur`;
    read_loop: LOOP
        FETCH `cur` INTO `order_id`;
        IF `done` THEN LEAVE read_loop; END IF;
        
        -- Полностью отгружаем все позиции данного заказа
        UPDATE `order_item` 
        SET `shipped` = `quantity`
        WHERE `order_link` = `order_id` AND `shipped` < `quantity`;
        
    END LOOP;
    CLOSE `cur`;
    
    COMMIT;
END$$
DELIMITER ;

DELIMITER $$

-- 2. Списание резерва и фактического остатка при отгрузке
CREATE TRIGGER `after_shipped_update`
AFTER UPDATE ON `order_item`
FOR EACH ROW
BEGIN
    DECLARE diff INT;
    SET diff = NEW.shipped - OLD.shipped;
    IF diff > 0 THEN
        UPDATE `warehouse_stock` ws
        JOIN `reserved_stock` rs ON ws.warehouse_link = rs.warehouse_link AND ws.product_link = rs.product_link
        SET ws.reserved = ws.reserved - LEAST(rs.quantity, diff),
            ws.stored = ws.stored - LEAST(rs.quantity, diff)
        WHERE rs.order_link = NEW.order_link AND rs.product_link = NEW.product_id;
        
        DELETE FROM `reserved_stock`
        WHERE order_link = NEW.order_link AND product_link = NEW.product_id AND quantity <= diff;
        
        -- (необязательная аварийная обработка оставшегося diff – можно опустить)
    END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `auto_ship_new_order`;
DELIMITER $$
CREATE TRIGGER `auto_ship_new_order` AFTER INSERT ON `order`
FOR EACH ROW
BEGIN
    IF NEW.`shipment_date` IS NOT NULL AND NEW.`shipment_date` <= GetVirtualTime() THEN
        UPDATE `order_item` SET `shipped` = `quantity` 
        WHERE `order_link` = NEW.`id` AND `shipped` < `quantity`;
    END IF;
END$$
DELIMITER ;

CALL generate_orders();