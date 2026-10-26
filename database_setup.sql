CREATE DATABASE IF NOT EXISTS agrised
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE agrised;

CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    worker_id VARCHAR(100) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE farmer_submissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    location VARCHAR(100) NOT NULL,
    crop_type VARCHAR(100) NOT NULL,
    sacks INT(11) NOT NULL DEFAULT 0,
    weight DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    weight_unit ENUM('KG', 'LB') NOT NULL,
    land_size DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    land_unit ENUM('Hectares', 'Acres', 'SqMeters', 'SqFeet') NOT NULL,
    monthly_profit DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    file_land VARCHAR(255) DEFAULT NULL,
    file_crop VARCHAR(255) DEFAULT NULL,
    file_payslip VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_flagged TINYINT(1) NOT NULL DEFAULT 0,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    is_paused TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_farmer_submissions_user (user_id),
    KEY idx_farmer_submissions_status_submitted (status, submitted_at),
    KEY idx_farmer_submissions_benchmark (status, is_paused, location, crop_type),
    CONSTRAINT fk_farmer_submissions_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE farmer_submission_appeals (
    id INT(11) NOT NULL AUTO_INCREMENT,
    submission_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    status ENUM('pending', 'accepted', 'denied') NOT NULL DEFAULT 'pending',
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    admin_comment TEXT DEFAULT NULL,
    comment_updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_appeals_submission (submission_id, id),
    KEY idx_appeals_user (user_id),
    CONSTRAINT fk_appeals_submission
        FOREIGN KEY (submission_id) REFERENCES farmer_submissions (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_appeals_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_reports (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    category ENUM('bug', 'ux', 'other') NOT NULL,
    message VARCHAR(2000) NOT NULL,
    attachment_path VARCHAR(255) DEFAULT NULL,
    status ENUM('open', 'resolved') NOT NULL DEFAULT 'open',
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    admin_comment TEXT DEFAULT NULL,
    comment_updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_user_reports_user (user_id),
    KEY idx_user_reports_status_submitted (status, submitted_at),
    CONSTRAINT fk_user_reports_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
