<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();
$period = $_GET['period'] ?? 'daily';
$type = $_GET['type'] ?? 'csv';

$dateFilter = match ($period) {
    'weekly' => 'verified_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)',
    'monthly' => 'verified_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
    default => 'DATE(verified_at) = CURDATE()',
};

if ($type !== 'csv') {
    header('Location: reports.php');
    exit;
}

$filename = 'pandapickle_report_' . $period . '_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['PandaPickle Report - ' . ucfirst($period)]);
fputcsv($out, ['Generated', date('Y-m-d H:i:s')]);
fputcsv($out, []);

fputcsv($out, ['Payment ID', 'Type', 'Customer', 'Reference', 'Amount', 'Status', 'Verified At']);

$stmt = $db->query(
    "SELECT p.*, u.fullname, r.reservation_code, s.title AS session_title
     FROM payments p
     LEFT JOIN exclusive_reservations r ON r.id = p.reservation_id
     LEFT JOIN open_play_registrations reg ON reg.id = p.registration_id
     LEFT JOIN open_play_sessions s ON s.id = reg.session_id
     LEFT JOIN users u ON u.id = COALESCE(r.user_id, reg.user_id)
     WHERE p.payment_status = 'paid' AND {$dateFilter}
     ORDER BY p.verified_at DESC"
);

while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['id'],
        $row['payment_type'],
        $row['fullname'],
        $row['reservation_code'] ?? $row['session_title'],
        $row['amount'],
        $row['payment_status'],
        $row['verified_at'],
    ]);
}

fputcsv($out, []);
fputcsv($out, ['Reservation Report']);
fputcsv($out, ['Code', 'Customer', 'Court', 'Date', 'Hours', 'Amount', 'Status']);

$reservations = $db->query(
    'SELECT r.*, u.fullname, c.court_name FROM exclusive_reservations r
     JOIN users u ON u.id = r.user_id JOIN courts c ON c.id = r.court_id
     ORDER BY r.created_at DESC'
);
while ($r = $reservations->fetch()) {
    fputcsv($out, [$r['reservation_code'], $r['fullname'], $r['court_name'], $r['reservation_date'], $r['hours_reserved'], $r['total_amount'], $r['status']]);
}

fputcsv($out, []);
fputcsv($out, ['Open Play Report']);
fputcsv($out, ['Session', 'Customer', 'Team', 'Amount', 'Status']);

$regs = $db->query(
    'SELECT reg.*, u.fullname, s.title FROM open_play_registrations reg
     LEFT JOIN users u ON u.id = reg.user_id JOIN open_play_sessions s ON s.id = reg.session_id
     ORDER BY reg.created_at DESC'
);
while ($reg = $regs->fetch()) {
    $userName = $reg['user_name'] ?: $reg['fullname'] ?: 'Walk-in';
    $team = $userName . ' & ' . $reg['partner_name'];
    fputcsv($out, [$reg['title'], $reg['fullname'] ?: 'Walk-in', $team, $reg['total_amount'], $reg['status']]);
}

fclose($out);
exit;
