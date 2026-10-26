ALTER TABLE farmer_submissions
    MODIFY status ENUM('pending', 'approved', 'rejected', 'cancelled') NULL DEFAULT 'pending';
