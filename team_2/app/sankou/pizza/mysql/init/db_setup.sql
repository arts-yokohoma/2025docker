-- 既存のテーブルがあれば削除（リセット用）
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS allowed_zipcodes;
DROP TABLE IF EXISTS settings;

-- 1. 注文管理テーブル
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    pizza_size VARCHAR(10) NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    status INT DEFAULT 0, 
    -- 0=新規注文, 2=配達中(調理込), 3=完了
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. 配達許可エリア（郵便番号ホワイトリスト）
CREATE TABLE allowed_zipcodes (
    code VARCHAR(10) PRIMARY KEY,
    area_name VARCHAR(100) NOT NULL
);

-- テストデータ: この郵便番号のみ注文可能
INSERT INTO allowed_zipcodes (code, area_name) VALUES 
('123-4567', '東地区 (Zone A)'), 
('111-2222', '西地区 (Zone B)'), 
('999-0000', '中央区 (Zone C)');

-- 3. 設定（シフト人数管理）
CREATE TABLE settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value INT NOT NULL
);

-- 初期設定: ドライバー2名
INSERT INTO settings (setting_key, setting_value) VALUES ('total_drivers', 2);