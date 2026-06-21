<?php

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$reservationId = $_GET['reservation_id'] ?? 0;
$registrationId = $_GET['registration_id'] ?? 0;

// Handle receipt upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt'])) {
    $uploadError = null;
    $file = $_FILES['receipt'];
    
    // Validate file upload
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $uploadError = 'Only JPG, PNG, and GIF images are allowed.';
        } elseif ($file['size'] > $maxSize) {
            $uploadError = 'File size must not exceed 5MB.';
        } else {
            // Create uploads directory if it doesn't exist
            $uploadsDir = __DIR__ . '/uploads/receipts';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $extension;
            $targetPath = $uploadsDir . '/' . $filename;
            $dbPath = 'uploads/receipts/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Check if payment record exists
                $paymentId = null;
                if ($reservationId) {
                    $stmt = $db->prepare('SELECT id FROM payments WHERE reservation_id = ?');
                    $stmt->execute([$reservationId]);
                    $payment = $stmt->fetch();
                    $paymentId = $payment['id'] ?? null;
                    
                    // If no payment record exists, create one
                    if (!$paymentId) {
                        $stmt = $db->prepare('SELECT total_amount FROM exclusive_reservations WHERE id = ?');
                        $stmt->execute([$reservationId]);
                        $res = $stmt->fetch();
                        
                        $stmt = $db->prepare(
                            'INSERT INTO payments (reservation_id, payment_type, amount, payment_status, proof_image) 
                             VALUES (?, \'reservation\', ?, \'pending_verification\', ?)'
                        );
                        $stmt->execute([$reservationId, $res['total_amount'], $dbPath]);
                        $paymentId = $db->lastInsertId();
                    } else {
                        // Update existing payment record
                        $stmt = $db->prepare(
                            'UPDATE payments SET proof_image = ?, payment_status = \'pending_verification\' WHERE id = ?'
                        );
                        $stmt->execute([$dbPath, $paymentId]);
                    }
                } elseif ($registrationId) {
                    $stmt = $db->prepare('SELECT id FROM payments WHERE registration_id = ?');
                    $stmt->execute([$registrationId]);
                    $payment = $stmt->fetch();
                    $paymentId = $payment['id'] ?? null;
                    
                    // If no payment record exists, create one
                    if (!$paymentId) {
                        $stmt = $db->prepare('SELECT total_amount FROM open_play_registrations WHERE id = ?');
                        $stmt->execute([$registrationId]);
                        $reg = $stmt->fetch();
                        
                        $stmt = $db->prepare(
                            'INSERT INTO payments (registration_id, payment_type, amount, payment_status, proof_image) 
                             VALUES (?, \'open_play\', ?, \'pending_verification\', ?)'
                        );
                        $stmt->execute([$registrationId, $reg['total_amount'], $dbPath]);
                        $paymentId = $db->lastInsertId();
                    } else {
                        // Update existing payment record
                        $stmt = $db->prepare(
                            'UPDATE payments SET proof_image = ?, payment_status = \'pending_verification\' WHERE id = ?'
                        );
                        $stmt->execute([$dbPath, $paymentId]);
                    }
                }
                
                flash('success', 'Receipt uploaded successfully! Your payment is now pending admin verification.');
                header('Location: dashboard.php');
                exit;
            } else {
                $uploadError = 'Failed to upload file. Please try again.';
            }
        }
    } else {
        $uploadError = 'Error uploading file. Please try again.';
    }
    
    if ($uploadError) {
        flash('error', $uploadError);
    }
}

// Get reservation or registration details
$item = null;
$type = '';
if ($reservationId) {
    $stmt = $db->prepare('SELECT r.*, c.court_name, p.payment_status, p.amount, p.proof_image, r.total_amount 
                          FROM exclusive_reservations r 
                          JOIN courts c ON c.id = r.court_id
                          LEFT JOIN payments p ON p.reservation_id = r.id
                          WHERE r.id = ? AND r.user_id = ?');
    $stmt->execute([$reservationId, getCurrentUser()['id']]);
    $item = $stmt->fetch();
    $type = 'reservation';
} elseif ($registrationId) {
    $stmt = $db->prepare('SELECT reg.*, s.title, s.session_date, p.payment_status, p.amount, p.proof_image, reg.total_amount
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
                    <span style="color: #059669; font-weight: 700;">
                        <?php
                        // Show payment amount or calculate from reservation/registration
                        if ($type === 'reservation') {
                            $amount = $item['amount'] ?? $item['total_amount'];
                        } else {
                            $amount = $item['amount'] ?? $item['total_amount'];
                        }
                        echo e(formatMoney((float)$amount));
                        ?>
                    </span>
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

                <!-- Receipt Upload Section -->
                <div class="receipt-upload-section">
                    <h4 style="color: #059669; margin-top: 0; margin-bottom: 1rem;">
                        📎 Upload Payment Receipt
                    </h4>
                    
                    <?php
                    // Check if receipt already uploaded
                    $hasReceipt = !empty($item['proof_image']);
                    if ($hasReceipt):
                    ?>
                        <div class="alert alert-success" style="background: #d1fae5; border-color: #059669; color: #065f46;">
                            <strong>✅ Receipt Already Uploaded</strong><br>
                            Your payment is currently under admin verification. You will be notified once verified.
                        </div>
                        <div style="text-align: center; margin-top: 1rem;">
                            <img src="<?= e($item['proof_image']) ?>" alt="Uploaded Receipt" style="max-width: 100%; max-height: 400px; border: 2px solid #059669; border-radius: 8px;">
                        </div>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
                            <div class="form-group">
                                <label for="receipt" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                                    Select Receipt Image (JPG, PNG, GIF - Max 5MB)
                                </label>
                                <input 
                                    type="file" 
                                    id="receipt" 
                                    name="receipt" 
                                    accept="image/jpeg,image/jpg,image/png,image/gif" 
                                    required
                                    style="display: block; width: 100%; padding: 0.5rem; border: 2px solid #059669; border-radius: 6px;"
                                >
                                <small style="color: #666; display: block; margin-top: 0.5rem;">
                                    📸 Take a clear photo of your payment receipt or screenshot
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; background: #059669; padding: 0.75rem;">
                                ⬆️ Upload Receipt
                            </button>
                        </form>
                    <?php endif; ?>
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

.receipt-upload-section {
    background: #f8f9fa;
    border: 2px dashed #059669;
    border-radius: 8px;
    padding: 1.5rem;
    margin-top: 1.5rem;
}

.alert-success {
    background: #d1fae5;
    border: 1px solid #059669;
    color: #065f46;
    padding: 1rem;
    border-radius: 6px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
