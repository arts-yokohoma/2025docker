-- Add inquiry method/status columns to existing customer table
-- Safe to run multiple times.

ALTER TABLE IF EXISTS customer
  ADD COLUMN IF NOT EXISTS inquiry_method VARCHAR(10) NOT NULL DEFAULT 'email';

ALTER TABLE IF EXISTS customer
  ADD COLUMN IF NOT EXISTS inquiry_status VARCHAR(20) NOT NULL DEFAULT '未対応';

-- Ensure defaults match current app expectations
ALTER TABLE IF EXISTS customer
  ALTER COLUMN inquiry_status SET DEFAULT '未対応';

-- Backfill existing data from previous default values
UPDATE customer
   SET inquiry_status = '未対応'
 WHERE inquiry_status IS NULL
    OR inquiry_status = ''
    OR inquiry_status = 'new';
