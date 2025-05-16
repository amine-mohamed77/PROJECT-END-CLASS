-- Create database
CREATE DATABASE IF NOT EXISTS student_housing;
USE student_housing;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'owner') NOT NULL,
    university VARCHAR(100) NULL,
    profile_image VARCHAR(255) DEFAULT 'uploads/profile/default.jpg',
    bio TEXT NULL,
    phone VARCHAR(20) NULL,
    rating DECIMAL(3,2) DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Properties table
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    location VARCHAR(255) NOT NULL,
    nearby_university VARCHAR(100) NULL,
    property_type ENUM('apartment', 'house', 'room', 'dormitory') NOT NULL,
    beds INT NOT NULL,
    baths DECIMAL(3,1) NOT NULL,
    area DECIMAL(10,2) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    facilities TEXT NULL,
    nearby_facilities TEXT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Property images table
CREATE TABLE IF NOT EXISTS property_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Demands table
CREATE TABLE IF NOT EXISTS demands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    budget_min DECIMAL(10,2) NOT NULL,
    budget_max DECIMAL(10,2) NOT NULL,
    room_type ENUM('studio', 'shared', '1bedroom', 'other') NOT NULL,
    move_in_date DATE NOT NULL,
    duration VARCHAR(20) NOT NULL,
    description TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Favorites table
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, property_id)
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    property_id INT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
);

-- Insert sample data
INSERT INTO users (name, email, password, role, university, rating, created_at) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'Stanford University', 4.5, NOW()),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', NULL, 4.8, NOW()),
('Michael Johnson', 'michael@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', NULL, 4.2, NOW()),
('Sarah Williams', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'MIT', 4.7, NOW());

-- Insert sample properties
INSERT INTO properties (owner_id, title, description, price, location, nearby_university, property_type, beds, baths, area, facilities, nearby_facilities, created_at) VALUES
(2, 'Modern Studio Apartment', 'A cozy studio apartment perfect for students. Fully furnished with modern amenities and high-speed internet included.', 650, 'Near Stanford University, Palo Alto', 'Stanford University', 'apartment', 1, 1, 35, 'Wi-Fi,Furnished,Laundry,Air Conditioning', 'Grocery Store,Gym,Bus Stop,Library', NOW()),
(2, 'Shared 2-Bedroom Apartment', 'Private room in a shared 2-bedroom apartment. Common areas include kitchen and living room. Utilities included.', 450, '5 min from MIT, Cambridge', 'MIT', 'apartment', 1, 1, 20, 'Wi-Fi,Furnished,Shared Kitchen,Heating', 'Coffee Shop,Supermarket,Metro Station,Park', NOW()),
(3, 'Luxury Student Housing', 'Premium student accommodation with private bathroom and kitchenette. Building includes study rooms and fitness center.', 850, 'UCLA Campus Area, Los Angeles', 'UCLA', 'apartment', 1, 1, 40, 'Wi-Fi,Furnished,Private Bathroom,Study Desk', 'Campus Shuttle,Restaurants,Bookstore,Fitness Center', NOW()),
(3, 'Cozy Room in Shared House', 'Private room in a friendly house shared with 3 other students. Shared bathroom and kitchen facilities.', 380, 'University of Washington Area, Seattle', 'University of Washington', 'room', 1, 0.5, 18, 'Wi-Fi,Furnished,Shared Kitchen,Laundry', 'Bus Stop,Cafe,Grocery Store,Library', NOW()),
(2, 'Modern 1-Bedroom Apartment', 'Stylish 1-bedroom apartment in the heart of NYC. Perfect for students who value privacy and convenience.', 750, 'NYU Area, New York City', 'NYU', 'apartment', 1, 1, 45, 'Wi-Fi,Furnished,Full Kitchen,Air Conditioning', 'Subway Station,Restaurants,Laundromat,Gym', NOW()),
(3, 'Budget-Friendly Studio', 'Affordable studio apartment within walking distance to campus. Includes basic amenities and utilities.', 550, 'UC Berkeley Area, Berkeley', 'UC Berkeley', 'studio', 1, 1, 30, 'Wi-Fi,Partially Furnished,Kitchenette,Heating', 'Campus Shuttle,Convenience Store,Park,Cafe', NOW());

-- Insert sample property images
INSERT INTO property_images (property_id, image_path, is_primary, created_at) VALUES
(1, 'uploads/properties/property1_1.jpg', 1, NOW()),
(1, 'uploads/properties/property1_2.jpg', 0, NOW()),
(1, 'uploads/properties/property1_3.jpg', 0, NOW()),
(2, 'uploads/properties/property2_1.jpg', 1, NOW()),
(2, 'uploads/properties/property2_2.jpg', 0, NOW()),
(3, 'uploads/properties/property3_1.jpg', 1, NOW()),
(3, 'uploads/properties/property3_2.jpg', 0, NOW()),
(3, 'uploads/properties/property3_3.jpg', 0, NOW()),
(4, 'uploads/properties/property4_1.jpg', 1, NOW()),
(5, 'uploads/properties/property5_1.jpg', 1, NOW()),
(6, 'uploads/properties/property6_1.jpg', 1, NOW());

-- Insert sample demands
INSERT INTO demands (student_id, budget_min, budget_max, room_type, move_in_date, duration, description, created_at) VALUES
(1, 600, 800, 'studio', '2023-09-01', '9 months', 'Looking for a quiet studio apartment within walking distance to Stanford. Need good internet connection and preferably furnished.', NOW()),
(4, 400, 600, 'shared', '2023-08-15', '12 months', 'International student looking for a room in a shared apartment. Prefer female roommates and close to public transportation.', NOW()),
(1, 700, 900, '1bedroom', '2023-08-20', '12 months', 'Graduate student looking for a quiet 1-bedroom apartment. Must allow pets (have a small cat) and be close to campus.', NOW());

-- Insert sample favorites
INSERT INTO favorites (user_id, property_id, created_at) VALUES
(1, 2, NOW()),
(4, 3, NOW()),
(1, 5, NOW());