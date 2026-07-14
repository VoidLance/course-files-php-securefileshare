-- SecureFileShare schema
-- MySQL 8+ recommended. Other engines may work with tiny tweaks.

CREATE DATABASE IF NOT EXISTS secure_file_share
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE secure_file_share;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'premium', 'regular') NOT NULL DEFAULT 'regular',
    avatar_path VARCHAR(255) NULL,
    storage_quota_bytes BIGINT UNSIGNED NOT NULL DEFAULT 52428800,
    two_factor_secret VARCHAR(255) NULL,
    email_verified_at DATETIME NULL,
    reset_token VARCHAR(120) NULL,
    reset_token_expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_email_verified (email_verified_at)
);

CREATE TABLE IF NOT EXISTS files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    storage_name VARCHAR(255) NOT NULL UNIQUE,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    folder_name VARCHAR(120) NOT NULL DEFAULT 'root',
    tags VARCHAR(255) NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    iv_base64 VARCHAR(100) NOT NULL,
    version_number INT UNSIGNED NOT NULL DEFAULT 1,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_files_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_files_user_created (user_id, created_at),
    INDEX idx_files_folder (folder_name),
    FULLTEXT INDEX ft_files_search (original_name, tags, folder_name)
);

CREATE TABLE IF NOT EXISTS file_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_id INT UNSIGNED NOT NULL,
    storage_name VARCHAR(255) NOT NULL,
    iv_base64 VARCHAR(100) NOT NULL,
    checksum_sha256 CHAR(64) NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_file_versions_file FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    INDEX idx_versions_file (file_id, version_number)
);

CREATE TABLE IF NOT EXISTS share_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    permission ENUM('view', 'edit', 'download') NOT NULL DEFAULT 'view',
    expires_at DATETIME NOT NULL,
    password_hash VARCHAR(255) NULL,
    revoked_at DATETIME NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_share_file FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    CONSTRAINT fk_share_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_share_expires (expires_at),
    INDEX idx_share_revoked (revoked_at)
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    description VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_activity_user_time (user_id, created_at),
    INDEX idx_activity_event (event_type)
);

CREATE TABLE IF NOT EXISTS comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    mentions_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_file FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_comments_file_time (file_id, created_at)
);

CREATE TABLE IF NOT EXISTS workspaces (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_workspace_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_workspace_owner (owner_id)
);

CREATE TABLE IF NOT EXISTS workspace_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workspace_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('owner', 'editor', 'viewer') NOT NULL DEFAULT 'viewer',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_workspace_member_workspace FOREIGN KEY (workspace_id) REFERENCES workspaces(id) ON DELETE CASCADE,
    CONSTRAINT fk_workspace_member_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_workspace_member (workspace_id, user_id)
);

-- Seed one admin for local demo. Password: admin123
INSERT INTO users (name, email, password_hash, role, storage_quota_bytes, email_verified_at)
VALUES ('Admin', 'admin@example.com', '$2y$10$p9ap8UG95f8CC4uS3TB0Cen.wN3w44NfM6kR6vOB0fOkHn84qxdnK', 'admin', 524288000, NOW())
ON DUPLICATE KEY UPDATE email = email;
