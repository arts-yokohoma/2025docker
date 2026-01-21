-- Migration: add morning/evening kitchen/driver columns to `shifts`
ALTER TABLE shifts
  ADD COLUMN IF NOT EXISTS morning_kitchen INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS morning_driver INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS evening_kitchen INTEGER DEFAULT 0,
  ADD COLUMN IF NOT EXISTS evening_driver INTEGER DEFAULT 0;

-- Optionally migrate existing `kitchen_count`/`driver_count` into morning columns when empty
UPDATE shifts
SET morning_kitchen = COALESCE(morning_kitchen, 0) + COALESCE(kitchen_count, 0),
    morning_driver = COALESCE(morning_driver, 0) + COALESCE(driver_count, 0)
WHERE morning_kitchen = 0 AND morning_driver = 0;
