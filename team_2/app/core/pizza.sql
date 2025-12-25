-- Database ကို အသုံးပြုမည်
USE team_2_db;

-- Orders table ဆောက်ခြင်း
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    postal_code VARCHAR(20) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    order_status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- စမ်းသပ်ရန် Data အချို့ ထည့်ခြင်း
INSERT INTO orders (postal_code, phone_number, order_status) 
VALUES ('1112222', '0912345678', 'delivered');