ALTER TABLE farmer_submissions
    ADD COLUMN reviewed_at TIMESTAMP NULL DEFAULT NULL AFTER submitted_at;
