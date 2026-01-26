-- Create users table
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
