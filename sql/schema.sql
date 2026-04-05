-- ============================================================
-- FeyFay Media - News Blog CMS - Complete Database Schema
-- Run this file in MySQL to create database and all tables
-- ============================================================

CREATE DATABASE IF NOT EXISTS feyfay_media;
USE feyfay_media;

-- ------------------------------------------------------------
-- Users: admin (full access) and staff (post/edit only, no delete/settings/ads)
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('staff', 'admin') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Categories
-- ------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Posts (with SEO and featured flag)
-- ------------------------------------------------------------
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    summary TEXT,
    content LONGTEXT NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    category_id INT NOT NULL,
    author_id INT NOT NULL,
    status ENUM('draft', 'published') DEFAULT 'draft',
    published_at DATETIME DEFAULT NULL,
    is_featured TINYINT(1) DEFAULT 0,
    is_sponsored TINYINT(1) DEFAULT 0,
    is_event TINYINT(1) DEFAULT 0,
    event_type VARCHAR(80) DEFAULT NULL,
    event_city VARCHAR(120) DEFAULT NULL,
    event_location VARCHAR(255) DEFAULT NULL,
    event_start_at DATETIME DEFAULT NULL,
    event_end_at DATETIME DEFAULT NULL,
    event_status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT NULL,
    views INT UNSIGNED DEFAULT 0,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description VARCHAR(320) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_slug (slug),
    INDEX idx_category_id (category_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_featured (is_featured),
    INDEX idx_status_created (status, created_at),
    INDEX idx_status_views (status, views),
    INDEX idx_published_at (published_at),
    INDEX idx_is_event_start (is_event, event_start_at),
    INDEX idx_event_status (event_status),
    INDEX idx_event_city (event_city),
    INDEX idx_event_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Tags
-- ------------------------------------------------------------
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Post-Tags (many-to-many)
-- ------------------------------------------------------------
CREATE TABLE post_tags (
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Comments (pending/approved)
-- ------------------------------------------------------------
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'approved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Settings (site + ads)
-- ------------------------------------------------------------
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(100) NOT NULL DEFAULT 'FEYFAY INVESTMENT',
    site_logo VARCHAR(255) DEFAULT NULL,
    site_description TEXT,
    contact_email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    facebook VARCHAR(255) DEFAULT NULL,
    twitter VARCHAR(255) DEFAULT NULL,
    instagram VARCHAR(255) DEFAULT NULL,
    youtube VARCHAR(255) DEFAULT NULL,
    footer_text TEXT,
    ads_header TEXT,
    ads_sidebar TEXT,
    ads_article TEXT,
    ads_homepage TEXT,
    show_admin_link TINYINT(1) DEFAULT 1,
    radio_name VARCHAR(100) DEFAULT NULL,
    radio_description TEXT,
    stream_url VARCHAR(500) DEFAULT NULL,
    embed_code TEXT,
    radio_is_live TINYINT(1) DEFAULT 0,
    now_playing VARCHAR(255) DEFAULT NULL,
    radio_button_text VARCHAR(50) DEFAULT 'Listen Live'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Subscribers (newsletter)
-- ------------------------------------------------------------
CREATE TABLE subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Default data
-- ------------------------------------------------------------

-- Default admin (password: password - CHANGE IN PRODUCTION!)
INSERT INTO users (name, username, email, password, role) VALUES
('Admin', 'admin', 'admin@feyfaymedia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample categories (event focused)
INSERT INTO categories (name, slug) VALUES
('Business Events', 'business-events'),
('Networking', 'networking'),
('Investment Forums', 'investment-forums'),
('Community Programs', 'community-programs'),
('Career Opportunities', 'career-opportunities');

-- Single row for settings
INSERT INTO settings (id, site_name, site_description, contact_email, footer_text) VALUES
(1, 'FEYFAY INVESTMENT', 'Jukwaa rasmi la taarifa sahihi kuhusu matukio na fursa za kuunganisha jamii.', 'contact@feyfaymedia.com', '© 2025 FEYFAY INVESTMENT. All rights reserved.');
