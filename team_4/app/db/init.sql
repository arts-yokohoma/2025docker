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
    '$2y$12$9.QvtP8LQe8mNb1Dn6/PTe6pYege7kQ9j7.HDL8YbruJmVBxdrWCK'
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE username = 'admin'
);
