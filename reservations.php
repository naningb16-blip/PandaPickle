<?php

require_once __DIR__ . '/includes/auth.php';
requireLogin();

// Redirect admins to admin panel - these pages are for customers only
if (isAdmin()) {
    flash('error', 'Admins should use the Admin panel to manage reservations.');
    header('Location: admin/reservations.php');
    exit;
}

$user = getCurrentUser();
$db = getDB();
$error = '';

$courts = $db->query('SELECT * FROM courts WHERE status = \'active\' ORDER BY court_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reservation'])) {
    checkCSRF();
    $courtId = (int) ($_POST['court_id'] ?? 0);
    $date = $_POST['reservation_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $hours = (float) ($_POST['hours_reserved'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';

    if ($courtId <= 0 || $date === '' || $startTime === '' || $hours <= 0) {
        $error = 'Please fill in all reservation fields.';
    } else {
        $startTimeFormatted = date('H:i:s', strtotime($startTime));
        $endTime = calculateEndTime($startTimeFormatted, $hours);

        if (!isWithinOperatingHours($startTimeFormatted, $endTime)) {
            $error = 'Reservation must be within operating hours (5:00 AM – 10:00 PM). End time cannot exceed 10:00 PM.';
        } elseif (hasReservationOverlap($courtId, $date, $startTimeFormatted, $endTime)) {
            $error = 'This time slot overlaps with an existing reservation. Please choose another time.';
        } else {
            // 🔒 PREVENT DUPLICATE SUBMISSION: Check if user already has pending/approved reservation for same court, date, and time
            $duplicateCheck = $db->prepare(
                'SELECT COUNT(*) FROM exclusive_reservations 
                 WHERE user_id = ? AND court_id = ? AND reservation_date = ? 
                 AND start_time = ? AND status IN (\'pending\', \'approved\')
                 AND created_at > NOW() - INTERVAL \'5 minutes\''
            );
            $duplicateCheck->execute([$user['id'], $courtId, $date, $startTimeFormatted]);
            
            if ((int) $duplicateCheck->fetchColumn() > 0) {
                $error = 'You already have a reservation for this court, date, and time. Please check your reservations below.';
            } else {
                $totalAmount = $hours * HOURLY_RATE;
                $code = generateReservationCode();

                try {
                    // Use transaction to prevent race conditions
                    $db->beginTransaction();

                    $stmt = $db->prepare(
                        'INSERT INTO exclusive_reservations
                         (reservation_code, user_id, court_id, reservation_date, start_time, end_time, hours_reserved, hourly_rate, total_amount, payment_method, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'pending\')'
                    );
                    $stmt->execute([
                        $code, $user['id'], $courtId, $date, $startTimeFormatted, $endTime,
                        $hours, HOURLY_RATE, $totalAmount, $paymentMethod,
                    ]);

                    $reservationId = (int) $db->lastInsertId();

                    // Create payment record with unpaid status (admin will update this)
                    $payStmt = $db->prepare(
                        'INSERT INTO payments (reservation_id, payment_type, amount, payment_status) 
                         VALUES (?, \'reservation\', ?, \'unpaid\')'
                    );
                    $payStmt->execute([$reservationId, $totalAmount]);

                    $db->commit();

                    flash('success', "Reservation {$code} submitted! Total: " . formatMoney($totalAmount) . '. Awaiting admin approval.');
                    header('Location: reservations.php');
                    exit;
                } catch (PDOException $e) {
                    $db->rollBack();
                    $error = 'Failed to create reservation. Please try again.';
                    error_log('Reservation error: ' . $e->getMessage());
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_proof'])) {
    flash('error', 'Payment proof upload is no longer required. Please wait for admin approval.');
    header('Location: reservations.php');
    exit;
}

$reservations = $db->prepare(
    'SELECT r.*, c.court_name, p.payment_status
     FROM exclusive_reservations r
     JOIN courts c ON c.id = r.court_id
     LEFT JOIN payments p ON p.reservation_id = r.id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC'
);
$reservations->execute([$user['id']]);
$reservations = $reservations->fetchAll();

$pageTitle = 'Reservations - PandaPickle';
$currentPage = 'reservations';
$extraScripts = <<<'JS'
<script>
function updateTotal() {
    const hours = parseFloat(document.getElementById('hours_reserved').value) || 0;
    const rate = 250;
    const total = hours * rate;
    document.getElementById('total_display').textContent = 'PHP ' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    if (hours > 0) {
        const start = document.getElementById('start_time').value;
        if (start) {
            const parts = start.split(':');
            const d = new Date();
            d.setHours(parseInt(parts[0]), parseInt(parts[1]), 0);
            d.setTime(d.getTime() + hours * 3600000);
            const end = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            document.getElementById('end_time_display').textContent = end;
        }
    }
}
document.getElementById('hours_reserved')?.addEventListener('input', updateTotal);
document.getElementById('start_time')?.addEventListener('input', updateTotal);
</script>
JS;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Exclusive Court Reservations</h1>
        <p>PHP 250/hour &bull; Operating hours 5:00 AM – 10:00 PM</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>New Reservation</h3></div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="court_id">Court</label>
                        <select id="court_id" name="court_id" required>
                            <?php foreach ($courts as $court): ?>
                                <option value="<?= (int) $court['id'] ?>"><?= e($court['court_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reservation_date">Date</label>
                            <input type="date" id="reservation_date" name="reservation_date"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="start_time">Start Time</label>
                            <input type="time" id="start_time" name="start_time"
                                   min="05:00" max="21:00" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="hours_reserved">Number of Hours</label>
                        <input type="number" id="hours_reserved" name="hours_reserved"
                               min="1" max="17" step="0.5" value="1" required oninput="updateTotal()">
                        <p class="form-hint">End time: <span id="end_time_display">—</span> (must not exceed 10:00 PM)</p>
                    </div>
                    <div class="form-group">
                        <label>Total Amount</label>
                        <div class="amount-display" id="total_display">PHP 250.00</div>
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="cashless">Cashless (GCash/Bank Transfer)</option>
                        </select>
                    </div>
                    <button type="submit" name="create_reservation" class="btn btn-primary btn-block">Submit Reservation</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>How It Works</h3></div>
            <div class="card-body">
                <ol class="text-muted">
                    <li>Select date, start time, and hours</li>
                    <li>Choose payment method (Cash or Cashless)</li>
                    <li>System calculates end time and total (Hours × PHP 250)</li>
                    <li>Submit reservation (status: Pending)</li>
                    <li>Admin reviews and approves your reservation</li>
                    <li>Make payment using selected method</li>
                </ol>
                <p class="mt-2"><strong>Examples:</strong></p>
                <p class="text-muted">2 hours = PHP 500 &bull; 3 hours = PHP 750</p>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-header"><h3>My Reservations</h3></div>
        <div class="card-body table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th><th>Court</th><th>Date</th><th>Time</th>
                        <th>Hours</th><th>Total</th><th>Payment Method</th><th>Payment Status</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td><strong><?= e($r['reservation_code']) ?></strong></td>
                        <td><?= e($r['court_name']) ?></td>
                        <td><?= e(formatDate($r['reservation_date'])) ?></td>
                        <td><?= e(formatTime($r['start_time'])) ?> - <?= e(formatTime($r['end_time'])) ?></td>
                        <td><?= e($r['hours_reserved']) ?></td>
                        <td><?= e(formatMoney((float) $r['total_amount'])) ?></td>
                        <td><span class="badge badge-muted"><?= e(ucfirst($r['payment_method'])) ?></span></td>
                        <td><span class="badge <?= statusBadgeClass($r['payment_status'] ?? 'unpaid') ?>"><?= e($r['payment_status'] ?? 'unpaid') ?></span></td>
                        <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= e($r['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
