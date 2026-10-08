ALTER TABLE bookings
    ADD COLUMN traveler_title ENUM('Mr','Mrs','Ms','Dr') NOT NULL DEFAULT 'Mr',
    ADD COLUMN first_name VARCHAR(50) NOT NULL DEFAULT '',
    ADD COLUMN last_name VARCHAR(50) NOT NULL DEFAULT '',
    ADD COLUMN contact_email VARCHAR(150) NOT NULL DEFAULT '',
    ADD COLUMN contact_phone VARCHAR(20) NOT NULL DEFAULT '',
    ADD COLUMN additional_info TEXT NULL;
