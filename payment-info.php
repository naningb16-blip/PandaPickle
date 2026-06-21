<?php

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$reservationId = $_GET['reservation_id'] ?? 0;
$registrationId = $_GET['registration_id'] ?? 0;

// Get reservation or registration details
$item = null;
$type = '';
if ($reservationId) {
    $stmt = $db->prepare('SELECT r.*, c.court_name, p.payment_status, p.amount 
                          FROM exclusive_reservations r 
                          JOIN courts c ON c.id = r.court_id
                          LEFT JOIN payments p ON p.reservation_id = r.id
                          WHERE r.id = ? AND r.user_id = ?');
    $stmt->execute([$reservationId, getCurrentUser()['id']]);
    $item = $stmt->fetch();
    $type = 'reservation';
} elseif ($registrationId) {
    $stmt = $db->prepare('SELECT reg.*, s.title, s.session_date, p.payment_status, p.amount
                          FROM open_play_registrations reg
                          JOIN open_play_sessions s ON s.id = reg.session_id
                          LEFT JOIN payments p ON p.registration_id = reg.id
                          WHERE reg.id = ? AND reg.user_id = ?');
    $stmt->execute([$registrationId, getCurrentUser()['id']]);
    $item = $stmt->fetch();
    $type = 'open_play';
}

if (!$item) {
    flash('error', 'Invalid reservation or registration.');
    header('Location: dashboard.php');
    exit;
}

// Get payment settings
$settings = $db->query('SELECT * FROM payment_settings WHERE is_active = TRUE ORDER BY display_order')->fetchAll();
$paymentData = [];
foreach ($settings as $s) {
    $paymentData[$s['setting_key']] = $s['setting_value'];
}

$pageTitle = 'Payment Information - PandaPickle';
$currentPage = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>💳 Payment Information</h1>
        <p>Complete your payment for your booking</p>
    </div>

    <div class="grid-2" style="gap: 2rem;">
        <!-- Left: Booking Details -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Booking Details</h3>
            </div>
            <div class="card-body">
                <?php if ($type === 'reservation'): ?>
                    <div class="info-row">
                        <strong>Reservation Code:</strong>
                        <span><?= e($item['reservation_code']) ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Court:</strong>
                        <span><?= e($item['court_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Date:</strong>
                        <span><?= e(formatDate($item['reservation_date'])) ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Time:</strong>
                        <span><?= e(formatTime($item['start_time'])) ?> - <?= e(formatTime($item['end_time'])) ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Hours:</strong>
                        <span><?= e($item['hours_reserved']) ?> hours</span>
                    </div>
                <?php else: ?>
                    <div class="info-row">
                        <strong>Session:</strong>
                        <span><?= e($item['title']) ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Date:</strong>
                        <span><?= e(formatDate($item['session_date'])) ?></span>
                    </div>
                    <div class="info-row">
                        <strong>Team:</strong>
                        <span><?= e($item['user_name'] ?: 'You') ?> & <?= e($item['partner_name']) ?></span>
                    </div>
                <?php endif; ?>
                
                <hr style="margin: 1.5rem 0;">
                
                <div class="info-row" style="font-size: 1.2rem;">
                    <strong>Amount to Pay:</strong>
                    <span style="color: #059669; font-weight: 700;"><?= e(formatMoney((float)$item['amount'])) ?></span>
                </div>
                
                <div class="info-row">
                    <strong>Status:</strong>
                    <span class="badge <?= statusBadgeClass($item['payment_status'] ?? 'unpaid') ?>">
                        <?= e($item['payment_status'] ?? 'unpaid') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Right: Payment Methods -->
        <div class="card">
            <div class="card-header" style="background: #059669; color: white;">
                <h3 style="margin: 0; color: white;">💰 Payment Methods</h3>
            </div>
            <div class="card-body">
                <div class="payment-method-box">
                    <h4 style="color: #059669; margin-top: 0;">
                        <svg width="24" height="24" style="vertical-align: middle; margin-right: 0.5rem;" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                        GCash
                    </h4>
                    <div style="font-size: 1.3rem; color: #047857; font-weight: 600; margin: 1rem 0;">
                        <?= e($paymentData['gcash_number'] ?? 'Not set') ?>
                    </div>
                    <div style="color: #666; margin-bottom: 1rem;">
                        Name: <?= e($paymentData['gcash_name'] ?? 'Not set') ?>
                    </div>
                </div>

                <div class="payment-method-box">
                    <h4 style="color: #059669; margin-top: 0;">
                        <svg width="24" height="24" style="vertical-align: middle; margin-right: 0.5rem;" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                        </svg>
                        Bank Transfer
                    </h4>
                    <div style="margin: 1rem 0;">
                        <strong>Bank:</strong> <span style="color: #047857;"><?= e($paymentData['bank_name'] ?? 'Not set') ?></span><br>
                        <strong>Account Number:</strong><br>
                        <span style="font-size: 1.2rem; color: #047857; font-weight: 600;">
                            <?= e($paymentData['bank_account_number'] ?? 'Not set') ?>
                        </span><br>
                        <small style="color: #666;">
                            <?= e($paymentData['bank_account_name'] ?? 'Not set') ?>
                        </small>
                    </div>
                </div>

                <div class="alert alert-warning" style="margin-top: 1.5rem;">
                    <strong>📝 Payment Instructions:</strong><br>
                    <?= nl2br(e($paymentData['payment_instructions'] ?? 'Please contact admin for payment instructions.')) ?>
                </div>

                <div class="alert alert-info">
                    <strong>📞 Questions or Need Help?</strong><br>
                    Contact us: <strong style="color: #047857;"><?= e($paymentData['contact_number'] ?? 'Not available') ?></strong>
                </div>

                <div style="margin-top: 2rem; text-align: center;">
                    <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}

.info-row:last-child {
    border-bottom: none;
}

.payment-method-box {
    background: #f0fdf4;
    border: 2px solid #059669;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.alert-warning {
    background: #fff3cd;
    border: 1px solid #ffeeba;
    color: #856404;
    padding: 1rem;
    border-radius: 6px;
}

.alert-info {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    color: #004085;
    padding: 1rem;
    border-radius: 6px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
