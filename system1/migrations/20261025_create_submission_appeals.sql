CREATE TABLE farmer_submission_appeals (
    id INT(11) NOT NULL AUTO_INCREMENT,
    submission_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    message VARCHAR(1000) NOT NULL,
    status ENUM('pending', 'accepted', 'denied') NOT NULL DEFAULT 'pending',
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_appeals_submission (submission_id, id),
    KEY idx_appeals_user (user_id)
);
