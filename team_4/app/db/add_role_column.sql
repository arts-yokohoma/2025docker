-- Add role column to users table if it doesn't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'staff' CHECK (role IN ('supervisor', 'staff'));

-- Update existing admin user to be supervisor
UPDATE users SET role = 'supervisor' WHERE username = 'admin';

-- Example: Create a test staff user (optional)
-- INSERT INTO users (username, password, role) 
-- VALUES ('staff1', '$2y$12$...', 'staff')
-- WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'staff1');
