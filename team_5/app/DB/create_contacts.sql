-- PostgreSQL table for contacts (phone is primary key)
CREATE TABLE IF NOT EXISTS customer (
  phone VARCHAR(50) PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  inquiry_method VARCHAR(10) NOT NULL DEFAULT 'email',
  inquiry_status VARCHAR(20) NOT NULL DEFAULT '未対応',
  message TEXT NOT NULL,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
