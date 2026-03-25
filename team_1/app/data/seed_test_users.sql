-- Seed test users for development and testing
-- All passwords are: "password123" (bcrypt hashed with cost 10)
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- Clear existing test users if needed (optional - comment out if you want to keep existing users)
-- DELETE FROM users WHERE username LIKE 'test_%';

-- Admin Users (role_id = 1)
INSERT INTO users (username, email, name, surname, phone, password, role_id) VALUES
('test_admin1', 'admin1@test.com', '太郎', '管理者', '090-1111-1111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('test_admin2', 'admin2@test.com', '次郎', '管理者', '090-1111-2222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('test_admin3', 'admin3@test.com', '花子', '管理者', '090-1111-3333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Manager Users (role_id = 2)
INSERT INTO users (username, email, name, surname, phone, password, role_id) VALUES
('test_manager1', 'manager1@test.com', '太郎', 'マネージャー', '090-2222-1111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
('test_manager2', 'manager2@test.com', '次郎', 'マネージャー', '090-2222-2222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
('test_manager3', 'manager3@test.com', '三郎', 'マネージャー', '090-2222-3333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
('test_manager4', 'manager4@test.com', '美咲', 'マネージャー', '090-2222-4444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);

-- Kitchen Users (role_id = 3)
INSERT INTO users (username, email, name, surname, phone, password, role_id) VALUES
('test_kitchen1', 'kitchen1@test.com', '太郎', 'キッチン', '090-3333-1111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('test_kitchen2', 'kitchen2@test.com', '次郎', 'キッチン', '090-3333-2222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('test_kitchen3', 'kitchen3@test.com', '三郎', 'キッチン', '090-3333-3333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('test_kitchen4', 'kitchen4@test.com', '花子', 'キッチン', '090-3333-4444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('test_kitchen5', 'kitchen5@test.com', '美咲', 'キッチン', '090-3333-5555', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3);

-- Driver/Delivery Users (role_id = 4)
INSERT INTO users (username, email, name, surname, phone, password, role_id) VALUES
('test_driver1', 'driver1@test.com', '太郎', 'ドライバー', '090-4444-1111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4),
('test_driver2', 'driver2@test.com', '次郎', 'ドライバー', '090-4444-2222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4),
('test_driver3', 'driver3@test.com', '三郎', 'ドライバー', '090-4444-3333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4),
('test_driver4', 'driver4@test.com', '四郎', 'ドライバー', '090-4444-4444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4),
('test_driver5', 'driver5@test.com', '花子', 'ドライバー', '090-4444-5555', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4),
('test_driver6', 'driver6@test.com', '美咲', 'ドライバー', '090-4444-6666', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4);

-- Verify insertion
SELECT 
    u.id,
    u.username,
    u.email,
    u.name,
    u.surname,
    r.name as role_name,
    u.created_at
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE u.username LIKE 'test_%'
ORDER BY r.id, u.username;

-- Summary
SELECT 
    r.name as role,
    COUNT(*) as user_count
FROM users u
JOIN roles r ON u.role_id = r.id
WHERE u.username LIKE 'test_%'
GROUP BY r.name
ORDER BY r.id;
