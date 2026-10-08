USE globetrek_adventures;

ALTER TABLE bookings
    ADD COLUMN payment_status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
    ADD COLUMN payhere_order_id VARCHAR(50) NULL;
