UPDATE farmer_submissions
SET crop_type = 'Sugarcane'
WHERE crop_type = 'Sugar';

ALTER TABLE farmer_submissions
MODIFY crop_type ENUM('Rice', 'Onion', 'Sugarcane') NOT NULL;
