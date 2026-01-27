-- ============================================
-- PIZZA HOUSE DATABASE SCHEMA
-- ============================================

-- 1. Users table (for admin - already exists from your init.sql)
-- You already have this, so I'm including it for reference
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL
);

-- Insert default admin user ONLY if not exists
INSERT INTO users (username, password)
SELECT
    'admin',
    '$2y$12$f3e0hPilqmyGG8k2yF.K/OC5D0yVSpQIYNlC0mtea2CVyXVFQcyVq' -- bcrypt hash for 'admin123'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admin'
);

-- ============================================
-- NEW TABLES FOR PIZZA ORDERING SYSTEM
-- ============================================

-- 2. Pizzas table (single pizza with 3 sizes)
CREATE TABLE IF NOT EXISTS pizzas (
    id SERIAL PRIMARY KEY,
    pizza_name VARCHAR(100) DEFAULT 'Classic Pizza',
    description TEXT DEFAULT 'Delicious pizza with tomato sauce, mozzarella cheese, and fresh basil',
    
    -- Three sizes with prices
    small_price DECIMAL(10,2) NOT NULL DEFAULT 800,
    medium_price DECIMAL(10,2) NOT NULL DEFAULT 1200,
    large_price DECIMAL(10,2) NOT NULL DEFAULT 1500,
    
    -- Additional info
    small_size VARCHAR(20) DEFAULT '20cm',
    medium_size VARCHAR(20) DEFAULT '30cm',
    large_size VARCHAR(20) DEFAULT '40cm',
    
    image_url TEXT DEFAULT 'https://images.unsplash.com/photo-1601924638867-3ec62c7e5c79',
    is_available BOOLEAN DEFAULT true,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shift_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    morning_staff INT DEFAULT 5,
    evening_staff INT DEFAULT 5,
    notes TEXT,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Orders table
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    
    -- Auto-generated order number
    order_number VARCHAR(50) UNIQUE NOT NULL,
    
    -- Customer information
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(100),
    customer_address TEXT NOT NULL,
    
    -- Order quantities (one pizza, three sizes)
    small_quantity INTEGER DEFAULT 0,
    medium_quantity INTEGER DEFAULT 0,
    large_quantity INTEGER DEFAULT 0,
    
    -- Prices at time of order (for historical reference)
    small_price DECIMAL(10,2) NOT NULL,
    medium_price DECIMAL(10,2) NOT NULL,
    large_price DECIMAL(10,2) NOT NULL,
    
    -- Calculated totals
    small_total DECIMAL(10,2) DEFAULT 0,
    medium_total DECIMAL(10,2) DEFAULT 0,
    large_total DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    
    -- Order status
    status VARCHAR(20) DEFAULT 'pending' 
        CHECK (status IN ('pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled')),
    
    -- Special requests
    special_instructions TEXT,
    
    -- Delivery information
    estimated_delivery_time TIMESTAMP,
    actual_delivery_time TIMESTAMP,
    
    -- Timestamps
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Function to generate order number
CREATE OR REPLACE FUNCTION generate_order_number()
RETURNS TRIGGER AS $$
BEGIN
    -- Format: PH-YYYYMMDD-00001
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        NEW.order_number := 'PH-' || 
                           to_char(CURRENT_DATE, 'YYYYMMDD') || '-' || 
                           LPAD(NEXTVAL('orders_order_number_seq')::text, 5, '0');
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 5. Sequence for order numbers
CREATE SEQUENCE IF NOT EXISTS orders_order_number_seq START 1;

-- 6. Trigger for auto-generating order number
DROP TRIGGER IF EXISTS set_order_number ON orders;
CREATE TRIGGER set_order_number
    BEFORE INSERT ON orders
    FOR EACH ROW
    EXECUTE FUNCTION generate_order_number();

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Insert default pizza (only one pizza with 3 sizes)
INSERT INTO pizzas (pizza_name, description, small_price, medium_price, large_price)
VALUES (
    'Classic Pizza',
    'Delicious pizza with tomato sauce, mozzarella cheese, and fresh basil',
    800,
    1200,
    1500
) ON CONFLICT DO NOTHING;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Index for faster order lookups
CREATE INDEX IF NOT EXISTS idx_orders_order_number ON orders(order_number);
CREATE INDEX IF NOT EXISTS idx_orders_customer_phone ON orders(customer_phone);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_order_date ON orders(order_date);

-- ============================================
-- VIEWS FOR REPORTING
-- ============================================

-- View for daily sales
CREATE OR REPLACE VIEW daily_sales AS
SELECT 
    DATE(order_date) as sale_date,
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    SUM(small_quantity) as small_sold,
    SUM(medium_quantity) as medium_sold,
    SUM(large_quantity) as large_sold
FROM orders
WHERE status != 'cancelled'
GROUP BY DATE(order_date)
ORDER BY sale_date DESC;

-- View for order summary
CREATE OR REPLACE VIEW order_summary AS
SELECT 
    o.*,
    (o.small_quantity + o.medium_quantity + o.large_quantity) as total_pizzas
FROM orders o
ORDER BY o.order_date DESC;

-- ============================================
-- HELPER FUNCTIONS
-- ============================================

-- Function to calculate order totals
CREATE OR REPLACE FUNCTION calculate_order_totals()
RETURNS TRIGGER AS $$
BEGIN
    -- Calculate size totals
    NEW.small_total := NEW.small_quantity * NEW.small_price;
    NEW.medium_total := NEW.medium_quantity * NEW.medium_price;
    NEW.large_total := NEW.large_quantity * NEW.large_price;
    
    -- Calculate subtotal
    NEW.subtotal := NEW.small_total + NEW.medium_total + NEW.large_total;
    
    -- Calculate tax (10%)
    NEW.tax_amount := NEW.subtotal * 0.10;
    
    -- Calculate delivery fee (free if subtotal > 2000)
    IF NEW.subtotal >= 2000 THEN
        NEW.delivery_fee := 0;
    ELSE
        NEW.delivery_fee := 300;
    END IF;
    
    -- Calculate total amount
    NEW.total_amount := NEW.subtotal + NEW.tax_amount + NEW.delivery_fee - NEW.discount_amount;

    -- Set estimated delivery time (30 minutes from order)
    NEW.estimated_delivery_time := NEW.order_date + INTERVAL '30 minutes';
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger for auto-calculating totals
DROP TRIGGER IF EXISTS calculate_totals ON orders;
CREATE TRIGGER calculate_totals
    BEFORE INSERT OR UPDATE ON orders
    FOR EACH ROW
    EXECUTE FUNCTION calculate_order_totals();