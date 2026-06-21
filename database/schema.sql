-- PostgreSQL Schema for PandaPickle
-- Converted from MySQL

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'customer' CHECK (role IN ('customer', 'admin')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courts table
CREATE TABLE IF NOT EXISTS courts (
    id SERIAL PRIMARY KEY,
    court_name VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'maintenance'))
);

-- Exclusive reservations table
CREATE TABLE IF NOT EXISTS exclusive_reservations (
    id SERIAL PRIMARY KEY,
    reservation_code VARCHAR(20) NOT NULL UNIQUE,
    user_id INTEGER DEFAULT NULL,
    customer_name VARCHAR(100) DEFAULT NULL,
    customer_phone VARCHAR(20) DEFAULT NULL,
    court_id INTEGER NOT NULL,
    reservation_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    hours_reserved DECIMAL(4, 1) NOT NULL,
    hourly_rate DECIMAL(10, 2) NOT NULL DEFAULT 250.00,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL DEFAULT 'cash' CHECK (payment_method IN ('cash', 'cashless')),
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'completed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE
);

CREATE INDEX idx_court_date ON exclusive_reservations(court_id, reservation_date);

-- Open play sessions table
CREATE TABLE IF NOT EXISTS open_play_sessions (
    id SERIAL PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_players INTEGER NOT NULL DEFAULT 20,
    fee_per_player DECIMAL(10, 2) NOT NULL DEFAULT 50.00,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'cancelled', 'completed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Open play registrations table
CREATE TABLE IF NOT EXISTS open_play_registrations (
    id SERIAL PRIMARY KEY,
    session_id INTEGER NOT NULL,
    user_id INTEGER DEFAULT NULL,
    user_name VARCHAR(100) DEFAULT NULL,
    partner_name VARCHAR(100) NOT NULL,
    match_preference VARCHAR(20) NOT NULL DEFAULT 'random' CHECK (match_preference IN ('random', 'friends')),
    friend_group VARCHAR(100) DEFAULT NULL,
    contact_phone VARCHAR(20) DEFAULT NULL,
    payment_method VARCHAR(20) NOT NULL DEFAULT 'cash' CHECK (payment_method IN ('cash', 'cashless')),
    total_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected', 'completed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES open_play_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Open play matches table
CREATE TABLE IF NOT EXISTS open_play_matches (
    id SERIAL PRIMARY KEY,
    session_id INTEGER NOT NULL,
    match_round INTEGER NOT NULL DEFAULT 1,
    game_number INTEGER DEFAULT NULL,
    player1_reg_id INTEGER NOT NULL,
    player2_reg_id INTEGER NOT NULL,
    player3_reg_id INTEGER NOT NULL,
    player4_reg_id INTEGER NOT NULL,
    match_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (match_status IN ('pending', 'playing', 'completed')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES open_play_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (player1_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (player2_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (player3_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (player4_reg_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE
);

CREATE INDEX idx_session_round ON open_play_matches(session_id, match_round);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    reservation_id INTEGER DEFAULT NULL,
    registration_id INTEGER DEFAULT NULL,
    payment_type VARCHAR(20) NOT NULL CHECK (payment_type IN ('reservation', 'open_play')),
    proof_image VARCHAR(255) DEFAULT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status VARCHAR(30) NOT NULL DEFAULT 'unpaid' CHECK (payment_status IN ('unpaid', 'pending_verification', 'paid', 'rejected')),
    verified_by INTEGER DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES exclusive_reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (registration_id) REFERENCES open_play_registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Password resets table
CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default courts
INSERT INTO courts (court_name, status) VALUES
('Court 1', 'active'),
('Court 2', 'active')
ON CONFLICT DO NOTHING;
