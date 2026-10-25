ALTER TABLE user_reports
    ADD COLUMN attachment_path VARCHAR(255) NULL AFTER message,
    ADD COLUMN admin_comment TEXT NULL AFTER resolved_at,
    ADD COLUMN comment_updated_at TIMESTAMP NULL DEFAULT NULL AFTER admin_comment;

ALTER TABLE farmer_submission_appeals
    ADD COLUMN admin_comment TEXT NULL AFTER reviewed_at,
    ADD COLUMN comment_updated_at TIMESTAMP NULL DEFAULT NULL AFTER admin_comment;
