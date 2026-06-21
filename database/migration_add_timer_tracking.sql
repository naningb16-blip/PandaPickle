-- Add timer tracking columns to exclusive_reservations table
-- This allows timers to persist across page reloads

ALTER TABLE exclusive_reservations 
ADD COLUMN timer_started_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN timer_status VARCHAR(20) DEFAULT 'not_started' CHECK (timer_status IN ('not_started', 'running', 'stopped', 'completed'));

-- Add index for quick timer queries
CREATE INDEX idx_timer_status ON exclusive_reservations(timer_status);

-- Comments
COMMENT ON COLUMN exclusive_reservations.timer_started_at IS 'Timestamp when the timer was started for this reservation';
COMMENT ON COLUMN exclusive_reservations.timer_status IS 'Current status of the reservation timer';
