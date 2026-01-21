-- PostgreSQL table for time slots per shift_date
CREATE TABLE IF NOT EXISTS time_slots (
  id SERIAL PRIMARY KEY,
  shift_date DATE NOT NULL,
  slot_start TIME NOT NULL,
  slot_end TIME NOT NULL,
  capacity INTEGER NOT NULL DEFAULT 1,
  available BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_shift_date FOREIGN KEY (shift_date) REFERENCES shifts(shift_date) ON DELETE CASCADE,
  CONSTRAINT uniq_shift_slot UNIQUE (shift_date, slot_start, slot_end)
);

-- Example insert (optional):
-- INSERT INTO time_slots (shift_date, slot_start, slot_end, capacity) VALUES ('2026-01-20','11:00','11:30',5);
