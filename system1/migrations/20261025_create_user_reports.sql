CREATE TABLE user_reports (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    category ENUM('bug', 'ux', 'other') NOT NULL,
    message VARCHAR(2000) NOT NULL,
    status ENUM('open', 'resolved') NOT NULL DEFAULT 'open',
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_user_reports_user (user_id),
    KEY idx_user_reports_status_submitted (status, submitted_at)
);
