-- team_2/app/pizza.sql

CREATE DATABASE IF NOT EXISTS team_2_db;
USE team_2_db;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    pizza_type VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- စမ်းသပ်ဖို့ Data အတု ၂ ခုလောက် ထည့်မယ်
INSERT INTO orders (customer_name, pizza_type, quantity) VALUES ('Mg Mg', 'Pepperoni', 2);
INSERT INTO orders (customer_name, pizza_type, quantity) VALUES ('Ma Ma', 'Chicken Cheesy', 1);