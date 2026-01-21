-- Populate `time_slots` for each `shift_date` using per-period staff counts
-- Morning: 10:00-16:00, Evening: 16:00-22:00. 30-minute slots starting at 10:00..21:30
INSERT INTO time_slots (shift_date, slot_start, slot_end, capacity, available)
SELECT s.shift_date,
       (gs)::time AS slot_start,
       (gs + interval '30 minutes')::time AS slot_end,
       (CASE WHEN (gs::time) < time '16:00'
             THEN COALESCE(s.morning_kitchen,0) + COALESCE(s.morning_driver,0)
             ELSE COALESCE(s.evening_kitchen,0) + COALESCE(s.evening_driver,0)
        END) AS capacity,
       (CASE WHEN (CASE WHEN (gs::time) < time '16:00'
                        THEN COALESCE(s.morning_kitchen,0) + COALESCE(s.morning_driver,0)
                        ELSE COALESCE(s.evening_kitchen,0) + COALESCE(s.evening_driver,0)
                   END) > 0 THEN TRUE ELSE FALSE END) AS available
FROM shifts s
CROSS JOIN LATERAL generate_series(
  (s.shift_date + time '10:00'),
  (s.shift_date + time '21:30'),
  interval '30 minutes'
) AS gs
ON CONFLICT DO NOTHING;
