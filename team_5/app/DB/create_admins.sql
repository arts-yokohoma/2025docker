-- Create admins table for Team 5 admin users
CREATE TABLE IF NOT EXISTS admin (
  id SERIAL PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP WITH TIME ZONE
);

-- Recommended: insert a user with a secure bcrypt hash using your tooling.
-- Example (do NOT use plaintext passwords):
-- INSERT INTO admins (username, password_hash) VALUES ('admin', '<bcrypt-hash>');
