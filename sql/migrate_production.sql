-- FeyFay Media: Idempotent migration (safe to run multiple times)
-- Adds production columns and indexes only if they don't exist.

USE feyfay_media;

-- Add is_sponsored to posts (if missing)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'is_sponsored') = 0,
    'ALTER TABLE posts ADD COLUMN is_sponsored TINYINT(1) DEFAULT 0 AFTER is_featured',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add ads_homepage to settings (if missing)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'ads_homepage') = 0,
    'ALTER TABLE settings ADD COLUMN ads_homepage TEXT AFTER ads_article',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add show_admin_link to settings (if missing)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'show_admin_link') = 0,
    'ALTER TABLE settings ADD COLUMN show_admin_link TINYINT(1) DEFAULT 1',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add radio columns to settings (if missing)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'radio_name') = 0,
    'ALTER TABLE settings ADD COLUMN radio_name VARCHAR(100) DEFAULT NULL AFTER show_admin_link, ADD COLUMN radio_description TEXT AFTER radio_name, ADD COLUMN stream_url VARCHAR(500) DEFAULT NULL AFTER radio_description, ADD COLUMN embed_code TEXT AFTER stream_url, ADD COLUMN radio_is_live TINYINT(1) DEFAULT 0 AFTER embed_code, ADD COLUMN now_playing VARCHAR(255) DEFAULT NULL AFTER radio_is_live, ADD COLUMN radio_button_text VARCHAR(50) DEFAULT ''Listen Live'' AFTER now_playing',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index idx_status_created (if missing)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND INDEX_NAME = 'idx_status_created') = 0,
    'ALTER TABLE posts ADD INDEX idx_status_created (status, created_at)',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index idx_status_views (if missing)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND INDEX_NAME = 'idx_status_views') = 0,
    'ALTER TABLE posts ADD INDEX idx_status_views (status, views)',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add published_at for scheduled publishing (if missing)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'published_at') = 0,
    'ALTER TABLE posts ADD COLUMN published_at DATETIME DEFAULT NULL AFTER status',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE posts SET published_at = created_at WHERE status = 'published' AND published_at IS NULL;

-- Roles: ensure users.role is ENUM('staff','admin') and add is_active
-- Step 1: add is_active if missing
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_active') = 0,
    'ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: migrate role to staff/admin only when legacy role type includes editor/author
SET @legacy_role = (SELECT IFNULL(COLUMN_TYPE, '') FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role' LIMIT 1);

SET @needs_role_migration = (SELECT IF(@legacy_role LIKE '%editor%' OR @legacy_role LIKE '%author%', 1, 0));

SET @sql = (SELECT IF(
    @needs_role_migration = 1 AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role_new') = 0,
    'ALTER TABLE users ADD COLUMN role_new ENUM(''staff'', ''admin'') NOT NULL DEFAULT ''staff'' AFTER role',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    @needs_role_migration = 1 AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role_new') = 1,
    'UPDATE users SET role_new = IF(role = ''admin'', ''admin'', ''staff'')',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    @needs_role_migration = 1,
    'ALTER TABLE users DROP COLUMN role',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    @needs_role_migration = 1 AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role_new') = 1,
    'ALTER TABLE users CHANGE COLUMN role_new role ENUM(''staff'', ''admin'') NOT NULL DEFAULT ''staff''',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Event model columns on posts
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'is_event') = 0,
    'ALTER TABLE posts ADD COLUMN is_event TINYINT(1) DEFAULT 0 AFTER is_sponsored',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'event_type') = 0,
    'ALTER TABLE posts ADD COLUMN event_type VARCHAR(80) DEFAULT NULL AFTER is_event',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'event_city') = 0,
    'ALTER TABLE posts ADD COLUMN event_city VARCHAR(120) DEFAULT NULL AFTER event_type',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'event_location') = 0,
    'ALTER TABLE posts ADD COLUMN event_location VARCHAR(255) DEFAULT NULL AFTER event_city',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'event_start_at') = 0,
    'ALTER TABLE posts ADD COLUMN event_start_at DATETIME DEFAULT NULL AFTER event_location',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'event_end_at') = 0,
    'ALTER TABLE posts ADD COLUMN event_end_at DATETIME DEFAULT NULL AFTER event_start_at',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'event_status') = 0,
    'ALTER TABLE posts ADD COLUMN event_status ENUM(''upcoming'', ''ongoing'', ''completed'', ''cancelled'') DEFAULT NULL AFTER event_end_at',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Event indexes
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND INDEX_NAME = 'idx_is_event_start') = 0,
    'ALTER TABLE posts ADD INDEX idx_is_event_start (is_event, event_start_at)',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND INDEX_NAME = 'idx_event_status') = 0,
    'ALTER TABLE posts ADD INDEX idx_event_status (event_status)',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND INDEX_NAME = 'idx_event_city') = 0,
    'ALTER TABLE posts ADD INDEX idx_event_city (event_city)',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND INDEX_NAME = 'idx_event_type') = 0,
    'ALTER TABLE posts ADD INDEX idx_event_type (event_type)',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
