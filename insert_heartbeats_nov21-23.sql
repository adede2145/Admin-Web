-- ============================================
-- Manual Kiosk Heartbeat Data Insertion
-- For Nov 21, 22, 23, 2024
-- ============================================

-- INSTRUCTIONS:
-- 1. First, check which kiosk_id is currently active by running:
--    SELECT kiosk_id, location, is_active, last_seen FROM kiosks WHERE is_active = 1;
-- 
-- 2. Replace 'YOUR_KIOSK_ID' and 'YOUR_LOCATION' below with the actual values
-- 3. Run these INSERT statements in your MySQL database

-- ============================================
-- Option 1: If you know the kiosk_id (e.g., kiosk_id = 1)
-- ============================================

-- Insert heartbeat for Nov 21, 2024
INSERT INTO kiosk_heartbeats (kiosk_id, last_seen, location, created_at, updated_at)
VALUES 
(1, '2024-11-21 12:00:00', 'IT Department - Ground Floor', NOW(), NOW());

-- Insert heartbeat for Nov 22, 2024
INSERT INTO kiosk_heartbeats (kiosk_id, last_seen, location, created_at, updated_at)
VALUES 
(1, '2024-11-22 12:00:00', 'IT Department - Ground Floor', NOW(), NOW());

-- Insert heartbeat for Nov 23, 2024 (today)
INSERT INTO kiosk_heartbeats (kiosk_id, last_seen, location, created_at, updated_at)
VALUES 
(1, '2024-11-23 12:00:00', 'IT Department - Ground Floor', NOW(), NOW());


-- ============================================
-- Option 2: If you have kiosk_id = 7 (IT Office)
-- ============================================

-- Uncomment these if your active kiosk is kiosk_id = 7

-- INSERT INTO kiosk_heartbeats (kiosk_id, last_seen, location, created_at, updated_at)
-- VALUES 
-- (7, '2024-11-21 12:00:00', 'IT Office', NOW(), NOW());

-- INSERT INTO kiosk_heartbeats (kiosk_id, last_seen, location, created_at, updated_at)
-- VALUES 
-- (7, '2024-11-22 12:00:00', 'IT Office', NOW(), NOW());

-- INSERT INTO kiosk_heartbeats (kiosk_id, last_seen, location, created_at, updated_at)
-- VALUES 
-- (7, '2024-11-23 12:00:00', 'IT Office', NOW(), NOW());


-- ============================================
-- Verification Query
-- ============================================
-- After inserting, run this to verify the data was inserted correctly:

SELECT 
    kiosk_id, 
    last_seen, 
    location, 
    created_at 
FROM kiosk_heartbeats 
WHERE DATE(last_seen) IN ('2024-11-21', '2024-11-22', '2024-11-23')
ORDER BY last_seen;


-- ============================================
-- Alternative: Insert for ALL active kiosks
-- ============================================
-- If you want to insert heartbeats for all currently active kiosks:

-- For Nov 21
INSERT INTO kiosk_heartbeats (kiosk_id, last_seen, location, created_at, updated_at)
SELECT 
    kiosk_id, 
    '2024-11-21 12:00:00' as last_seen,
    location,
    NOW() as created_at,
    NOW() as updated_at
FROM kiosks 
WHERE is_active = 1;

-- For Nov 22
INSERT INTO kiosk_heartbeats (kiosk_id, last_seen, location, created_at, updated_at)
SELECT 
    kiosk_id, 
    '2024-11-22 12:00:00' as last_seen,
    location,
    NOW() as created_at,
    NOW() as updated_at
FROM kiosks 
WHERE is_active = 1;

-- For Nov 23
INSERT INTO kiosk_heartbeats (kiosk_id, last_seen, location, created_at, updated_at)
SELECT 
    kiosk_id, 
    '2024-11-23 12:00:00' as last_seen,
    location,
    NOW() as created_at,
    NOW() as updated_at
FROM kiosks 
WHERE is_active = 1;
