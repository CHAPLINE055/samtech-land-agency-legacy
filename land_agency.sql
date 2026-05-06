-- -----------------------------------------------------
-- LandAgency Database Skeleton
-- -----------------------------------------------------

CREATE DATABASE IF NOT EXISTS land_agency;
USE land_agency;

-- ----------------------------
-- Admins Table
-- ----------------------------
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    otp_code VARCHAR(6) NULL,
    otp_expiry DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Optional default admin (password: admin123)
INSERT INTO admins (username, password, email) VALUES 
('admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFXq6S9bBoe7N7a/V6jqMHVZzVJt2P/S', 'admin@example.com');

-- ----------------------------
-- Agents Table
-- ----------------------------
CREATE TABLE IF NOT EXISTS agents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------
-- Clients Table
-- ----------------------------
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------
-- Properties Table
-- ----------------------------
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    county VARCHAR(100),
    sub_county VARCHAR(100) DEFAULT NULL,
    ward VARCHAR(100) DEFAULT NULL,
    price DECIMAL(12,2),
    size VARCHAR(50),
    type ENUM('Sale','Rent') DEFAULT 'Sale',
    image VARCHAR(255),
    features TEXT,
    description TEXT,
    availability ENUM('Available','Sold') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);



-- ----------------------------
-- Bookings Table
-- ----------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    property_id INT NOT NULL,
    booking_date DATE NOT NULL,
    message TEXT,
    status ENUM('Pending', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending',
    viewed TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);
CREATE TABLE client_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property VARCHAR(255),
    name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    message TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
 CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    message TEXT NOT NULL,
    admin_note TEXT,
    status VARCHAR(32) DEFAULT 'pending',
    follow_up_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE ai_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_message TEXT NOT NULL,
    ai_response TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------
-- Property Gallery Table
-- ----------------------------
CREATE TABLE IF NOT EXISTS property_gallery (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- ----------------------------
-- Settings Table
-- ----------------------------
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  meta_key VARCHAR(50) NOT NULL UNIQUE,
  meta_value TEXT
);
