-- Create database
CREATE DATABASE IF NOT EXISTS student_housing;
USE student_housing;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    role ENUM('student', 'owner', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Property offers table
CREATE TABLE IF NOT EXISTS offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    owner_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    type ENUM('apartment', 'house', 'room', 'studio') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    location VARCHAR(200) NOT NULL,
    university VARCHAR(200) NOT NULL,
    bedrooms INT NOT NULL,
    bathrooms INT NOT NULL,
    area DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    amenities JSON,
    contact VARCHAR(100) NOT NULL,
    status ENUM('available', 'rented', 'pending') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Offer images table
CREATE TABLE IF NOT EXISTS offer_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    offer_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE
);

-- Housing demands table
CREATE TABLE IF NOT EXISTS demands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    type ENUM('apartment', 'house', 'room', 'studio') NOT NULL,
    max_price DECIMAL(10,2) NOT NULL,
    location VARCHAR(200) NOT NULL,
    university VARCHAR(200) NOT NULL,
    bedrooms INT NOT NULL,
    move_in_date DATE NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in months',
    description TEXT NOT NULL,
    preferences JSON,
    contact VARCHAR(100) NOT NULL,
    status ENUM('active', 'fulfilled', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Favorites table
CREATE TABLE IF NOT EXISTS favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    offer_id INT,
    demand_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    FOREIGN KEY (demand_id) REFERENCES demands(id) ON DELETE CASCADE,
    CHECK (offer_id IS NOT NULL OR demand_id IS NOT NULL)
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    offer_id INT,
    demand_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    FOREIGN KEY (demand_id) REFERENCES demands(id) ON DELETE CASCADE,
    CHECK (offer_id IS NOT NULL OR demand_id IS NOT NULL)
);

-- Create indexes
CREATE INDEX idx_offers_owner ON offers(owner_id);
CREATE INDEX idx_offers_type ON offers(type);
CREATE INDEX idx_offers_location ON offers(location);
CREATE INDEX idx_offers_university ON offers(university);
CREATE INDEX idx_offers_status ON offers(status);

CREATE INDEX idx_demands_student ON demands(student_id);
CREATE INDEX idx_demands_type ON demands(type);
CREATE INDEX idx_demands_location ON demands(location);
CREATE INDEX idx_demands_university ON demands(university);
CREATE INDEX idx_demands_status ON demands(status);

CREATE INDEX idx_favorites_user ON favorites(user_id);
CREATE INDEX idx_favorites_offer ON favorites(offer_id);
CREATE INDEX idx_favorites_demand ON favorites(demand_id);

CREATE INDEX idx_messages_sender ON messages(sender_id);
CREATE INDEX idx_messages_receiver ON messages(receiver_id);
CREATE INDEX idx_messages_offer ON messages(offer_id);
CREATE INDEX idx_messages_demand ON messages(demand_id); 