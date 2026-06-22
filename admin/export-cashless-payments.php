<?php
/**
 * Export Cashless Payments to Excel (CSV format)
 * Generates a CSV file with all cashless payments and their totals
 */

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Get date range from query parameters
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Build query
$query = 'SELECT p.*,
        u.fullname, u.email, u.phone,
        r.reservation_code, r.reservation_date, r.customer_name as res_customer_name, r.customer_phone as res_customer_phone,
        s.title AS session_title, s.session_date,
        reg.user_name as openplay_user_name, reg.partner_name, reg.contact_phone as openplay_phone
     FROM payments p
     LEFT JOIN exclusive_reservations r ON r.id = p.reservation_id
     LEFT JOIN open_play_registrations reg ON reg.id = p.registration_id
     LEFT JOIN open_play_sessions s ON s.id = reg.session_id
     LEFT JOIN users u ON u.id = COALESCE(r.user_id, reg.user_id)
     WHERE (r.payment_method = \'cashless\' OR reg.payment_method = \'cashless\')
     AND p.payment_status = \'paid\'';

$params = [];

if (!empty($startDate)) {
    $query .= ' AND (r.reservation_date >= ? OR s.session_date >= ?)';
    $params[] = $startDate;
    $params[] = $startDate;
}

if (!empty($endDate)) {
    $query .= ' AND (r.reservation_date <= ? OR s.session_date <= ?)';
    $params[] = $endDate;
    $params[] = $endDate;
}

$query .= ' ORDER BY p.verified_at DESC, p.created_at DESC';

if (!empty($params)) {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} else {
    $payments = $db->query($query)->fetchAll();
}

// Calculate total
$total = 0;
foreach ($payments as $payment) {
    $total += (float) $payment['amount'];
}

// Set headers for CSV download
$filename = 'cashless_payments_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
fputcsv($output, [
    'Payment ID',
    'Date Verified',
    'Type',
    'Reference/Code',
    'Customer Name',
    'Contact',
    'Email',
    'Booking Date',
    'Transfer Reference',
    'Amount (PHP)',
    'Payment Status'
]);

// Write data rows
foreach ($payments as $p) {
    $customerName = $p['fullname'] ?? $p['res_customer_name'] ?? $p['openplay_user_name'] ?? 'N/A';
    $contact = $p['phone'] ?? $p['res_customer_phone'] ?? $p['openplay_phone'] ?? 'N/A';
    $email = $p['email'] ?? 'N/A';
    $reference = $p['reservation_code'] ?? $p['session_title'] ?? 'N/A';
    $bookingDate = $p['reservation_date'] ?? $p['session_date'] ?? 'N/A';
    $type = $p['reservation_id'] ? 'Reservation' : 'Open Play';
    $verifiedDate = $p['verified_at'] ? date('Y-m-d H:i', strtotime($p['verified_at'])) : 'N/A';
    
    fputcsv($output, [
        $p['id'],
        $verifiedDate,
        $type,
        $reference,
        $customerName,
        $contact,
        $email,
        $bookingDate,
        $p['reference_number'] ?? 'N/A',
        number_format($p['amount'], 2, '.', ''),
        $p['payment_status']
    ]);
}

// Add empty row
fputcsv($output, []);

// Add total row
fputcsv($output, [
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    'TOTAL:',
    number_format($total, 2, '.', ''),
    ''
]);

// Add summary info
fputcsv($output, []);
fputcsv($output, ['Report Generated:', date('Y-m-d H:i:s')]);
fputcsv($output, ['Total Payments:', count($payments)]);
fputcsv($output, ['Total Amount:', 'PHP ' . number_format($total, 2)]);

if (!empty($startDate) || !empty($endDate)) {
    fputcsv($output, ['Date Range:', ($startDate ?: 'All') . ' to ' . ($endDate ?: 'All')]);
}

fclose($output);
exit;
