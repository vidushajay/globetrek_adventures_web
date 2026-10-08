-- ============================================================
-- GlobeTrek Adventures — Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS globetrek_adventures;
USE globetrek_adventures;

-- ------------------------------------------------------------
-- 1. USERS
-- Holds all three user types (customer, staff, admin).
-- `role` controls what a logged-in user is allowed to do.
-- ------------------------------------------------------------
CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,   -- store password_hash(), never plain text
    phone         VARCHAR(20),
    role          ENUM('customer', 'staff', 'admin') NOT NULL DEFAULT 'customer',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- 2. DESTINATIONS
-- Matches the image folders you already have.
-- ------------------------------------------------------------
CREATE TABLE destinations (
    destination_id INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,      -- e.g. "Galle"
    description     TEXT,
    image_folder    VARCHAR(100)                -- e.g. "Galle" -> ../img/Galle/
);

-- ------------------------------------------------------------
-- 3. PACKAGES
-- A bookable tour package, tied to one destination.
-- ------------------------------------------------------------
CREATE TABLE packages (
    package_id      INT AUTO_INCREMENT PRIMARY KEY,
    destination_id  INT NOT NULL,
    title           VARCHAR(150) NOT NULL,
    description     TEXT,
    price           DECIMAL(10,2) NOT NULL,
    duration_days   INT NOT NULL,
    max_travelers   INT DEFAULT 10,
    created_by      INT,                         -- staff user_id who added it
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (destination_id) REFERENCES destinations(destination_id)
        ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- 4. BOOKINGS
-- A customer booking a package.
-- ------------------------------------------------------------
CREATE TABLE bookings (
    booking_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,                -- the customer
    package_id      INT NOT NULL,
    travel_date     DATE NOT NULL,
    num_travelers   INT NOT NULL DEFAULT 1,
    total_price     DECIMAL(10,2) NOT NULL,
    status          ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    confirmed_by    INT,                         -- staff user_id who confirmed it
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(package_id)
        ON DELETE CASCADE,
    FOREIGN KEY (confirmed_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- 5. QUERIES
-- Customer inquiries sent to the agency (satisfies the
-- "submit queries to the travel management administration"
-- requirement in the brief).
-- ------------------------------------------------------------
CREATE TABLE queries (
    query_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT,                           -- nullable: allow guest queries too
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    subject       VARCHAR(150),
    message       TEXT NOT NULL,
    status        ENUM('open', 'answered', 'closed') NOT NULL DEFAULT 'open',
    handled_by    INT,                            -- staff/admin user_id
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL,
    FOREIGN KEY (handled_by) REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- Sample seed data (optional — useful for testing your pages)
-- ------------------------------------------------------------
INSERT INTO destinations (name, description, image_folder) VALUES
('Colombo',  'The vibrant commercial capital of Sri Lanka.', 'Colombo'),
('Galle',    'A historic Dutch fort city on the south coast.', 'Galle'),
('Ella',     'Hill country town famous for hiking and views.', 'Ella'),
('Dambulla', 'Home to ancient cave temples.', 'Dambulla'),
('Hatton',   'Tea country and Adam\'s Peak.', 'Hatton');
