ALTER TABLE farmer_submissions
    ADD COLUMN is_paused TINYINT(1) NOT NULL DEFAULT 0 AFTER is_flagged;
