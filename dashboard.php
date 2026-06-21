<?php

require_once __DIR__ . '/includes/auth.php';
requireLogin();

// Redirect admins to admin panel - this is the customer dashboard
if (isAdmin()) {
    header('Location: admin/index.php');
    exit;
}

$user = getCurrentUser();
$db = getDB();

$upcomingReservations = $db->prepare(
    'SELECT r.*, c.court_name, p.payment_status
     FROM exclusive_reservations r
     JOIN courts c ON c.id = r.court_id
     LEFT JOIN payments p ON p.reservation_id = r.id
     WHERE r.user_id = ? AND r.reservation_date >= CURRENT_DATE AND r.status IN (\'pending\', \'approved\')
     ORDER BY r.reservation_date, r.start_time LIMIT 5'
);
$upcomingReservations->execute([$user['id']]);
$upcomingReservations = $upcomingReservations->fetchAll();

$openPlayRegs = $db->prepare(
    'SELECT reg.*, s.title, s.session_date, s.start_time, s.end_time, p.payment_status
     FROM open_play_registrations reg
     JOIN open_play_sessions s ON s.id = reg.session_id
     LEFT JOIN payments p ON p.registration_id = reg.id
     WHERE reg.user_id = ? AND s.session_date >= CURRENT_DATE AND reg.status IN (\'pending\', \'approved\')
     ORDER BY s.session_date LIMIT 5'
);
$openPlayRegs->execute([$user['id']]);
$openPlayRegs = $openPlayRegs->fetchAll();

$history = $db->prepare(
    'SELECT r.*, c.court_name FROM exclusive_reservations r
     JOIN courts c ON c.id = r.court_id
     WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 10'
);
$history->execute([$user['id']]);
$history = $history->fetchAll();

$pageTitle = 'Dashboard - PandaPickle';
$currentPage = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Welcome, <?= e($user['fullname']) ?></h1>
        <p>Manage your reservations, open play registrations, and payments.</p>
    </div>

    <div class="grid-4 mb-2">
        <div class="stat-card">
            <div class="value"><?= count($upcomingReservations) ?></div>
            <div class="label">Upcoming Reservations</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= count($openPlayRegs) ?></div>
            <div class="label">Open Play Registrations</div>
        </div>
        <div class="stat-card">
            <div class="value"><?= count($history) ?></div>
            <div class="label">Total Bookings</div>
        </div>
        <div class="stat-card">
            <div class="value">PHP 250</div>
            <div class="label">Hourly Rate</div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-header">
                <h3>Upcoming Reservations</h3>
                <a href="reservations.php" class="btn btn-sm btn-primary">Book Court</a>
            </div>
            <div class="card-body table-wrap">
                <?php if (empty($upcomingReservations)): ?>
                    <p class="text-muted">No upcoming reservations.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Code</th><th>Date</th><th>Time</th><th>Payment</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingReservations as $r): ?>
                            <tr>
                                <td><?= e($r['reservation_code']) ?></td>
                                <td><?= e(formatDate($r['reservation_date'])) ?></td>
                                <td><?= e(formatTime($r['start_time'])) ?> - <?= e(formatTime($r['end_time'])) ?></td>
                                <td><span class="badge <?= statusBadgeClass($r['payment_status'] ?? 'unpaid') ?>"><?= e($r['payment_status'] ?? 'unpaid') ?></span></td>
                                <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= e($r['status']) ?></span></td>
                                <td>
                                    <?php if (($r['payment_status'] ?? 'unpaid') === 'unpaid'): ?>
                                        <a href="payment-info.php?reservation_id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-success">
                                            💳 Pay Now
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Open Play Registrations</h3>
                <a href="open-play.php" class="btn btn-sm btn-primary">Browse Sessions</a>
            </div>
            <div class="card-body table-wrap">
                <?php if (empty($openPlayRegs)): ?>
                    <p class="text-muted">No open play registrations.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Session</th><th>Date</th><th>Team</th><th>Status</th><th>Payment</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($openPlayRegs as $reg): ?>
                            <tr>
                                <td><?= e($reg['title']) ?></td>
                                <td><?= e(formatDate($reg['session_date'])) ?></td>
                                <td><?= e($reg['user_name'] ?: 'You') ?> & <?= e($reg['partner_name']) ?></td>
                                <td><span class="badge <?= statusBadgeClass($reg['status']) ?>"><?= e($reg['status']) ?></span></td>
                                <td><span class="badge <?= statusBadgeClass($reg['payment_status'] ?? 'unpaid') ?>"><?= e($reg['payment_status'] ?? 'unpaid') ?></span></td>
                                <td>
                                    <?php if (($reg['payment_status'] ?? 'unpaid') === 'unpaid'): ?>
                                        <a href="payment-info.php?registration_id=<?= (int)$reg['id'] ?>" class="btn btn-sm btn-success">
                                            💳 Pay Now
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-header"><h3>Reservation History</h3></div>
        <div class="card-body table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th><th>Court</th><th>Date</th><th>Hours</th>
                        <th>Amount</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $r): ?>
                    <tr>
                        <td><?= e($r['reservation_code']) ?></td>
                        <td><?= e($r['court_name']) ?></td>
                        <td><?= e(formatDate($r['reservation_date'])) ?></td>
                        <td><?= e($r['hours_reserved']) ?></td>
                        <td><?= e(formatMoney((float) $r['total_amount'])) ?></td>
                        <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= e($r['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
