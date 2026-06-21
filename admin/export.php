<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();
$period = $_GET['period'] ?? 'daily';
$type = $_GET['type'] ?? 'csv';

$dateFilter = match ($period) {
    'weekly' => 'verified_at >= CURRENT_DATE - INTERVAL \'7 days\'',
    'monthly' => 'verified_at >= CURRENT_DATE - INTERVAL \'30 days\'',
    default => 'DATE(verified_at) = CURRENT_DATE',
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
    "SELECT p.*, 
            COALESCE(r.customer_name, u_res.fullname) as customer_name,
            r.reservation_code, 
            s.title AS session_title
     FROM payments p
     LEFT JOIN exclusive_reservations r ON r.id = p.reservation_id
     LEFT JOIN open_play_registrations reg ON reg.id = p.registration_id
     LEFT JOIN open_play_sessions s ON s.id = reg.session_id
     LEFT JOIN users u_res ON u_res.id = r.user_id
     LEFT JOIN users u_reg ON u_reg.id = reg.user_id
     WHERE p.payment_status = 'paid' AND {$dateFilter}
     ORDER BY p.verified_at DESC"
);

while ($row = $stmt->fetch()) {
    $customerName = $row['customer_name'] ?: ($row['user_name'] ?? 'Walk-in');
    fputcsv($out, [
        $row['id'],
        $row['payment_type'],
        $customerName,
        $row['reservation_code'] ?? $row['session_title'],
        $row['amount'],
        $row['payment_status'],
        $row['verified_at'],
    ]);
}

fputcsv($out, []);
fputcsv($out, ['Reservation Report']);
fputcsv($out, ['Code', 'Type', 'Customer', 'Court', 'Date', 'Hours', 'Amount', 'Status']);

$reservations = $db->query(
    'SELECT r.*, 
            COALESCE(r.customer_name, u.fullname) as customer_name,
            c.court_name,
            CASE WHEN r.user_id IS NULL THEN \'walk-in\' ELSE \'online\' END as booking_type
     FROM exclusive_reservations r
     LEFT JOIN users u ON u.id = r.user_id 
     JOIN courts c ON c.id = r.court_id
     ORDER BY r.created_at DESC'
);
while ($r = $reservations->fetch()) {
    fputcsv($out, [
        $r['reservation_code'], 
        $r['booking_type'],
        $r['customer_name'], 
        $r['court_name'], 
        $r['reservation_date'], 
        $r['hours_reserved'], 
        $r['total_amount'], 
        $r['status']
    ]);
}

fputcsv($out, []);
fputcsv($out, ['Open Play Report']);
fputcsv($out, ['Session', 'Type', 'Customer', 'Team', 'Amount', 'Status']);

$regs = $db->query(
    'SELECT reg.*, 
            COALESCE(reg.user_name, u.fullname) as customer_name,
            s.title,
            CASE WHEN reg.user_id IS NULL THEN \'walk-in\' ELSE \'online\' END as booking_type
     FROM open_play_registrations reg
     LEFT JOIN users u ON u.id = reg.user_id 
     JOIN open_play_sessions s ON s.id = reg.session_id
     ORDER BY reg.created_at DESC'
);
while ($reg = $regs->fetch()) {
    $userName = $reg['customer_name'] ?: 'Walk-in';
    $team = $userName . ' & ' . $reg['partner_name'];
    fputcsv($out, [
        $reg['title'], 
        $reg['booking_type'],
        $userName, 
        $team, 
        $reg['total_amount'], 
        $reg['status']
    ]);
}

fclose($out);
exit;
