-- Database creation
CREATE DATABASE IF NOT EXISTS courier_db;
USE courier_db;

-- 1. Users table (Stores Customers and Admin)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    phone VARCHAR(20) NOT NULL,
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Riders table (Only Admin can create riders)
CREATE TABLE IF NOT EXISTS riders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    area VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Parcels table
CREATE TABLE IF NOT EXISTS parcels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_id VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    sender_phone VARCHAR(20) NOT NULL,
    pickup_address TEXT NOT NULL,
    receiver_name VARCHAR(100) NOT NULL,
    receiver_phone VARCHAR(20) NOT NULL,
    delivery_address TEXT NOT NULL,
    weight DECIMAL(10, 2) NOT NULL,
    parcel_type ENUM('Document', 'Package', 'Fragile') NOT NULL DEFAULT 'Package',
    status ENUM('Pending', 'Assigned', 'Picked Up', 'In Transit', 'Delivered') NOT NULL DEFAULT 'Pending',
    rider_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (rider_id) REFERENCES riders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Parcel Status History table (timeline)
CREATE TABLE IF NOT EXISTS parcel_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id INT NOT NULL,
    status ENUM('Pending', 'Assigned', 'Picked Up', 'In Transit', 'Delivered') NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Admin User (Default Username/Email: admin@courier.com, Password: admin123)
-- Note: The admin account will also be auto-seeded securely in config.php if not present in database.
INSERT INTO users (name, email, password, role, phone, status) 
VALUES ('System Admin', 'admin@courier.com', '$2y$10$wM626c9Y0K.7W7N1r6X5eO5.tM0q6eK9S1V5zJ9k2u8UfBv8u.aK', 'admin', '1234567890', 'active')
ON DUPLICATE KEY UPDATE email=email;
