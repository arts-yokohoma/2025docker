-- Seed a persistent staff user for testing and operations
-- Username: staff1
-- Password (plain): staffpass

INSERT INTO users (username, password, role)
VALUES ('staff1', '$2y$12$lDl2r9BH82sAaiye5ctu0eZOl0qhUG2CfYR6LEu6D583j8pUIHLJu', 'staff')
ON CONFLICT (username) DO UPDATE
  SET password = EXCLUDED.password,
      role = EXCLUDED.role;

-- To apply manually:
-- psql -h <host> -p <port> -U <user> -d <db> -f db/seed_staff_user.sql
