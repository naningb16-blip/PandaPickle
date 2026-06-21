<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();
$period = $_GET['period'] ?? 'daily';

$dateFilter = match ($period) {
    'weekly' => 'verified_at >= CURRENT_DATE - INTERVAL \'7 days\'',
    'monthly' => 'verified_at >= CURRENT_DATE - INTERVAL \'30 days\'',
    default => 'DATE(verified_at) = CURRENT_DATE',
};

$revenue = (float) $db->query(
    "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'paid' AND {$dateFilter}"
)->fetchColumn();

$reservationRevenue = (float) $db->query(
    "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'paid' AND payment_type = 'reservation' AND {$dateFilter}"
)->fetchColumn();

$openPlayRevenue = (float) $db->query(
    "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'paid' AND payment_type = 'open_play' AND {$dateFilter}"
)->fetchColumn();

$reservationReport = $db->query(
    'SELECT r.*, u.fullname, c.court_name, p.payment_status
     FROM exclusive_reservations r
     JOIN users u ON u.id = r.user_id
     JOIN courts c ON c.id = r.court_id
     LEFT JOIN payments p ON p.reservation_id = r.id
     ORDER BY r.created_at DESC LIMIT 50'
)->fetchAll();

$openPlayReport = $db->query(
    'SELECT reg.*, u.fullname, s.title, s.session_date, p.payment_status
     FROM open_play_registrations reg
     JOIN users u ON u.id = reg.user_id
     JOIN open_play_sessions s ON s.id = reg.session_id
     LEFT JOIN payments p ON p.registration_id = reg.id
     ORDER BY reg.created_at DESC LIMIT 50'
)->fetchAll();

$basePath = '../';
$pageTitle = 'Reports - Admin';
$currentPage = 'admin';
$adminPage = 'reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Reports</h1>
        <p>Revenue and activity reports with export options.</p>
    </div>
    <div class="admin-layout">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>
        <div>
            <div class="card mb-2">
                <div class="card-header">
                    <h3>Revenue Summary</h3>
                    <div class="no-print">
                        <a href="?period=daily" class="btn btn-sm <?= $period === 'daily' ? 'btn-primary' : 'btn-outline-dark' ?>">Daily</a>
                        <a href="?period=weekly" class="btn btn-sm <?= $period === 'weekly' ? 'btn-primary' : 'btn-outline-dark' ?>">Weekly</a>
                        <a href="?period=monthly" class="btn btn-sm <?= $period === 'monthly' ? 'btn-primary' : 'btn-outline-dark' ?>">Monthly</a>
                        <a href="export.php?type=csv&period=<?= e($period) ?>" class="btn btn-sm btn-dark">Export CSV</a>
                        <button onclick="window.print()" class="btn btn-sm btn-dark">Export PDF (Print)</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="grid-3">
                        <div class="stat-card"><div class="value"><?= e(formatMoney($revenue)) ?></div><div class="label">Total Revenue (<?= e($period) ?>)</div></div>
                        <div class="stat-card"><div class="value"><?= e(formatMoney($reservationRevenue)) ?></div><div class="label">Reservation Revenue</div></div>
                        <div class="stat-card"><div class="value"><?= e(formatMoney($openPlayRevenue)) ?></div><div class="label">Open Play Revenue</div></div>
                    </div>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header"><h3>Reservation Report</h3></div>
                <div class="card-body table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Code</th><th>Customer</th><th>Court</th><th>Date</th><th>Amount</th><th>Status</th><th>Payment</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservationReport as $r): ?>
                            <tr>
                                <td><?= e($r['reservation_code']) ?></td>
                                <td><?= e($r['fullname']) ?></td>
                                <td><?= e($r['court_name']) ?></td>
                                <td><?= e(formatDate($r['reservation_date'])) ?></td>
                                <td><?= e(formatMoney((float) $r['total_amount'])) ?></td>
                                <td><?= e($r['status']) ?></td>
                                <td><?= e($r['payment_status'] ?? 'unpaid') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Open Play Report</h3></div>
                <div class="card-body table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Session</th><th>Customer</th><th>Date</th><th>Team</th><th>Amount</th><th>Status</th><th>Payment</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($openPlayReport as $reg): ?>
                            <tr>
                                <td><?= e($reg['title']) ?></td>
                                <td><?= e($reg['fullname']) ?></td>
                                <td><?= e(formatDate($reg['session_date'])) ?></td>
                                <td><?= e($reg['user_name'] ?: $reg['fullname']) ?> & <?= e($reg['partner_name']) ?></td>
                                <td><?= e(formatMoney((float) $reg['total_amount'])) ?></td>
                                <td><?= e($reg['status']) ?></td>
                                <td><?= e($reg['payment_status'] ?? 'unpaid') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
