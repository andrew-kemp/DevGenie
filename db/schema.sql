-- DevGenie Full Schema (for new installs or upgrades)
-- Compatible with MySQL 5.7+ (no IF NOT EXISTS on ADD COLUMN/INDEX)

-- Admins table (local admin accounts, including super admin)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_super_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings table (for system/global settings)
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table (dev users, approvers, admins, super admins)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    display_name VARCHAR(200) NOT NULL,
    dev_email VARCHAR(255) NOT NULL,
    prod_email VARCHAR(255) DEFAULT NULL,
    notification_email_preference ENUM('dev','prod','both') DEFAULT 'dev',
    external_id VARCHAR(120),
    is_admin TINYINT(1) DEFAULT 0,
    is_approver TINYINT(1) DEFAULT 0,
    is_super_admin TINYINT(1) DEFAULT 0,
    local_password_hash VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_dev_email (dev_email)
);

-- Requests table (user account requests for approval workflow)
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    display_name VARCHAR(100),
    external_email VARCHAR(100),
    username_prefix VARCHAR(50),
    status VARCHAR(20) DEFAULT 'pending',
    requester_id INT,
    approver_id INT DEFAULT NULL,
    approval_comment VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_requester (requester_id),
    KEY idx_approver (approver_id)
);

-- === UPGRADE SECTION: For existing installs only ===
-- The following ALTERs are safe to run repeatedly; ignore errors "Duplicate column name" or "Duplicate key name"

-- Add missing columns to users table (ignore error if already exists)
ALTER TABLE users ADD COLUMN is_approver TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0;

-- Add missing column to admins table (ignore error if already exists)
ALTER TABLE admins ADD COLUMN is_super_admin TINYINT(1) DEFAULT 0;

-- Add missing columns to requests table (ignore error if already exists)
ALTER TABLE requests ADD COLUMN approver_id INT DEFAULT NULL;
ALTER TABLE requests ADD COLUMN approval_comment VARCHAR(255);
ALTER TABLE requests ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE requests ADD COLUMN display_name VARCHAR(100);
ALTER TABLE requests ADD COLUMN external_email VARCHAR(100);
ALTER TABLE requests ADD COLUMN username_prefix VARCHAR(50);

-- Add indexes to requests table (ignore error if already exists)
ALTER TABLE requests ADD INDEX idx_requester (requester_id);
ALTER TABLE requests ADD INDEX idx_approver (approver_id);

-- Set the first admin as super admin if only one exists (for upgrades)
UPDATE admins SET is_super_admin = 1 WHERE id = (SELECT id FROM (SELECT id FROM admins ORDER BY id ASC LIMIT 1) AS t);