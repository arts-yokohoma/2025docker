-- Database ကို အသုံးပြုမည်
USE team_2_db;

-- Orders table ဆောက်ခြင်း
IF NOT EXISTS (SELECT 1 FROM sysobjects WHERE name='orders')
CREATE TABLE orders (
    id INT IDENTITY(1,1) PRIMARY KEY,
    postal_code VARCHAR(20) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    order_status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- စမ်းသပ်ရန် Data အချို့ ထည့်ခြင်း
INSERT INTO orders (postal_code, phone_number, order_status) 
VALUES ('1112222', '0912345678', 'delivered');