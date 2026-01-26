# Troubleshooting: Order Creation Errors (注文処理に失敗しました)

## Как отладить ошибки создания заказа

После обновления `order_create.php`, теперь при ошибке показывается детальная информация.

---

## Частые ошибки и решения

### 1. **Customer insert failed: Unknown column 'consent'**

**Причина:** В таблице `customer` отсутствуют поля `consent` и `consent_time`

**Решение:**
```bash
# Запустите миграцию:
http://localhost/2025docker/team_1/data/migrate_add_consent_fields.php
```

---

### 2. **Order insert failed: Incorrect datetime value**

**Причина:** Колонка `delivery_time` имеет неправильный формат или тип VARCHAR вместо DATETIME

**Возможные значения в ошибке:**
- `delivery_time: ASAP` - строка вместо datetime
- `delivery_time: tomorrow_14:30` - не преобразовано в datetime

**Решение 1:** Проверить тип колонки в БД:
```sql
DESCRIBE orders;
```
Должно быть: `delivery_time  datetime  YES`

**Решение 2:** Изменить тип на DATETIME:
```bash
http://localhost/2025docker/team_1/data/migrate_delivery_time_to_datetime.php
```

**Решение 3:** Сделать колонку nullable:
```bash
http://localhost/2025docker/team_1/data/fix_delivery_time_nullable.php
```

---

### 3. **配達時間の形式が正しくありません**

**Причина:** `delivery_time` не соответствует формату `Y-m-d H:i:s`

**Ожидается:** `2026-01-22 14:30:00`
**Получено:** что-то другое

**Решение:** Проверить логику в `order_create.php` строки 78-133

---

### 4. **Invalid cart item: menuId=0**

**Причина:** В корзине товар без правильного ID

**Решение:**
1. Очистить localStorage корзины: `localStorage.removeItem('cart')`
2. Проверить структуру товаров в `cart.php`
3. Убедиться что `menu_id` передаётся правильно

---

### 5. **Order item insert failed: Cannot add or update a child row**

**Причина:** `menu_id` ссылается на несуществующий товар в таблице `menu`

**Решение:**
```sql
-- Проверить существующие товары:
SELECT id, name FROM menu WHERE active=1 AND deleted=0;

-- Проверить товар из ошибки:
SELECT * FROM menu WHERE id = <menu_id_из_ошибки>;
```

---

## Проверка данных в БД

### Проверить структуру таблицы customer:
```sql
DESCRIBE customer;
```

**Должно быть:**
```
name               varchar(100)  NO
email              varchar(255)  NO
phone              varchar(50)   NO
address            text          NO
consent            tinyint(1)    NO   (DEFAULT 0)
consent_time       datetime      YES
active             tinyint(1)    NO   (DEFAULT 1)
create_time        datetime      NO
update_time        datetime      NO
```

### Проверить структуру таблицы orders:
```sql
DESCRIBE orders;
```

**Должно быть:**
```
customer_id        int(11)       NO
delivery_address   text          NO
delivery_comment   text          YES
delivery_time      datetime      YES   ← ВАЖНО: datetime, не varchar!
total_price        int(11)       NO
status             varchar(30)   NO   (DEFAULT 'NEW')
create_time        datetime      NO
update_time        datetime      NO
```

---

## Логи ошибок

Все ошибки теперь записываются в PHP error log:
- Windows: `C:\xampp\php\logs\php_error_log`
- Linux: `/var/log/php/error.log`

Пример записи в логе:
```
[22-Jan-2026 14:30:00] Order creation failed: Order insert failed: ...
[22-Jan-2026 14:30:00] Stack trace: ...
```

---

## Тестирование

### 1. Тест с пустыми полями consent:
Если БД не обновлена, код автоматически пропустит поля consent

### 2. Тест с ASAP:
```
delivery_time должно быть: 2026-01-22 14:37:00
```

### 3. Тест с scheduled:
```
Из: tomorrow_14:30
В:  2026-01-23 14:30:00
```

---

## Быстрая диагностика

Выполните этот SQL для полной проверки:
```sql
-- 1. Проверка структуры customer
SHOW COLUMNS FROM customer LIKE '%consent%';

-- 2. Проверка типа delivery_time
SELECT COLUMN_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'orders' 
AND COLUMN_NAME = 'delivery_time';

-- 3. Проверка настроек магазина
SELECT * FROM store_hours WHERE id=1;

-- 4. Последние заказы
SELECT id, customer_id, delivery_time, status, create_time 
FROM orders 
ORDER BY id DESC 
LIMIT 5;
```

---

## В production

После тестирования замените строку в `order_create.php`:
```php
// DEVELOPMENT (показывает детали ошибки):
exit('注文処理に失敗しました<br><br>エラー詳細:<br>' . htmlspecialchars($e->getMessage()));

// PRODUCTION (скрывает детали):
exit('注文処理に失敗しました。もう一度お試しください。<br><a href="cart.php">← カートに戻る</a>');
```
