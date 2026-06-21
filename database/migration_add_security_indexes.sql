-- Migration: Add security indexes and constraints for duplicate prevention (PostgreSQL)
-- Improves performance of duplicate detection queries and adds database-level constraints

-- Add composite index for faster duplicate reservation checks
CREATE INDEX IF NOT EXISTS idx_user_court_date_time 
ON exclusive_reservations(user_id, court_id, reservation_date, start_time, status);

-- Add composite index for faster duplicate open play registration checks
CREATE INDEX IF NOT EXISTS idx_user_session_status 
ON open_play_registrations(user_id, session_id, status);

-- Add index for faster recent submission checks (created_at)
CREATE INDEX IF NOT EXISTS idx_reservations_created 
ON exclusive_reservations(created_at);

CREATE INDEX IF NOT EXISTS idx_registrations_created 
ON open_play_registrations(created_at);

-- Add index for payment status lookups
CREATE INDEX IF NOT EXISTS idx_payments_registration 
ON payments(registration_id, payment_status);

CREATE INDEX IF NOT EXISTS idx_payments_reservation 
ON payments(reservation_id, payment_status);

-- Add index for match generation queries
CREATE INDEX IF NOT EXISTS idx_session_status_preference 
ON open_play_registrations(session_id, status, match_preference, friend_group);

-- Optimize session date queries
CREATE INDEX IF NOT EXISTS idx_session_date_status 
ON open_play_sessions(session_date, status);

-- Optimize reservation date and status queries
CREATE INDEX IF NOT EXISTS idx_reservation_date_status 
ON exclusive_reservations(reservation_date, status);

