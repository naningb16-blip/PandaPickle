CREATE DATABASE IF NOT EXISTS pandapickle
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pandapickle;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS courts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    court_name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive', 'maintenance') NOT NULL DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS exclusive_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT DEFAULT NULL,
    customer_name VARCHAR(100) DEFAULT NULL,
    customer_phone VARCHAR(20) DEFAULT NULL,
    court_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    hours_reserved DECIMAL(4, 1) NOT NULL,
    hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 250.00,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'cashless') NOT NULL DEFAULT 'cash',
    status ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE,
    INDEX idx_court_date (court_id, reservation_date)
);

CREATE TABLE IF NOT EXISTS open_play_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_players INT NOT NULL DEFAULT 20,
    fee_per_player DECIMAL(10, 2) NOT NULL DEFAULT 50.00,
    status ENUM('active', 'cancelled', 'completed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS open_play_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    user_name VARCHAR(100) DEFAULT NULL,
    partner_name VARCHAR(100) NOT NULL,
    match_preference ENUM('random', 'friends') NOT NULL DEFAULT 'random',
    friend_group VARCHAR(100) DEFAULT NULL,
    contact_phone VARCHAR(20) DEFAULT NULL,
    payment_method ENUM('cash', 'cashless') NOT NULL DEFAULT 'cash',
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES open_play_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS open_play_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    match_round INT NOT NULL DEFAULT 1,
    player1_reg_id INT NOT NULL,
    player2_reg_id INT NOT NULL,
    player3_reg_id INT NOT NULL,
    player4_reg_id INT NOT NULL,
    match_status ENUM('pending', 'playing', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES open_play_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (player1_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (player2_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (player3_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (player4_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    INDEX idx_session_round (session_id, match_round)
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT DEFAULT NULL,
    registration_id INT DEFAULT NULL,
    payment_type ENUM('reservation', 'open_play') NOT NULL,
    proof_image VARCHAR(255) DEFAULT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('unpaid', 'pending_verification', 'paid', 'rejected') NOT NULL DEFAULT 'unpaid',
    verified_by INT DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES exclusive_reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (registration_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO courts (court_name, status) VALUES
('Court 1', 'active'),
('Court 2', 'active');
