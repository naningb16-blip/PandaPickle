<?php

require_once __DIR__ . '/../config/db.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void
{
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    startSession();
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function formatDate(string $date): string
{
    return date('M j, Y', strtotime($date));
}

function formatTime(string $time): string
{
    return date('g:i A', strtotime($time));
}

function formatMoney(float $amount): string
{
    return 'PHP ' . number_format($amount, 2);
}

function generateReservationCode(): string
{
    return 'RES-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function calculateEndTime(string $startTime, float $hours): string
{
    $start = strtotime($startTime);
    $end = $start + (int) round($hours * 3600);
    return date('H:i:s', $end);
}

function isWithinOperatingHours(string $startTime, string $endTime): bool
{
    $open = sprintf('%02d:00:00', COURT_OPEN_HOUR);
    $close = sprintf('%02d:00:00', COURT_CLOSE_HOUR);

    return $startTime >= $open && $endTime <= $close && $startTime < $endTime;
}

function hasReservationOverlap(int $courtId, string $date, string $startTime, string $endTime, ?int $excludeId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM exclusive_reservations
            WHERE court_id = ? AND reservation_date = ?
            AND status NOT IN (\'rejected\')
            AND start_time < ? AND end_time > ?';
    $params = [$courtId, $date, $endTime, $startTime];

    if ($excludeId) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

function getOpenPlayPlayerCount(int $sessionId): int
{
    $stmt = getDB()->prepare(
        'SELECT COUNT(*) FROM open_play_registrations
         WHERE session_id = ? AND status = \'approved\''
    );
    $stmt->execute([$sessionId]);
    return (int) $stmt->fetchColumn();
}

function getOpenPlayAvailableSlots(int $sessionId, int $maxPlayers): int
{
    return max(0, $maxPlayers - getOpenPlayPlayerCount($sessionId));
}

function generateFacebookPost(array $session): string
{
    $available = getOpenPlayAvailableSlots((int) $session['id'], (int) $session['max_players']);
    $fee = formatMoney((float) $session['fee_per_player']);

    return "🏓 OPEN PLAY SESSION\n\n"
        . "Date: " . formatDate($session['session_date']) . "\n"
        . "Time: " . formatTime($session['start_time']) . " - " . formatTime($session['end_time']) . "\n"
        . "Fee: {$fee} per Player\n"
        . "Available Slots: {$available}\n\n"
        . "Join now and enjoy friendly pickleball games.\n"
        . "Doubles format - 4 players per match (2v2).\n\n"
        . "#PandaPickle #Pickleball #OpenPlay";
}

function getPaymentForReservation(int $reservationId): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM payments WHERE reservation_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$reservationId]);
    $payment = $stmt->fetch();
    return $payment ?: null;
}

function getPaymentForRegistration(int $registrationId): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM payments WHERE registration_id = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$registrationId]);
    $payment = $stmt->fetch();
    return $payment ?: null;
}

function statusBadgeClass(string $status): string
{
    return match ($status) {
        'pending', 'pending_verification', 'unpaid' => 'badge-warning',
        'approved', 'paid', 'active', 'completed' => 'badge-success',
        'rejected', 'cancelled' => 'badge-danger',
        default => 'badge-muted',
    };
}

function uploadPaymentProof(array $file): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed, true)) {
        return ['success' => false, 'message' => 'Only JPG, PNG, WEBP, or GIF images are allowed.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File must be 5MB or smaller.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = 'proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $destination = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Could not save uploaded file.'];
    }

    return ['success' => true, 'path' => UPLOAD_URL . $filename];
}
