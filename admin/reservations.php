<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();
$admin = getCurrentUser();

// Get all courts for the form
$courts = $db->query('SELECT * FROM courts WHERE status = \'active\' ORDER BY court_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle walk-in reservation creation
    if (isset($_POST['create_walkin'])) {
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');
        $courtId = (int) ($_POST['court_id'] ?? 0);
        $reservationDate = $_POST['reservation_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $hourlyRate = (float) ($_POST['hourly_rate'] ?? HOURLY_RATE);
        
        $errors = [];
        
        if (empty($customerName)) {
            $errors[] = 'Customer name is required.';
        }
        if (empty($customerPhone)) {
            $errors[] = 'Customer phone is required.';
        }
        if ($courtId <= 0) {
            $errors[] = 'Please select a valid court.';
        }
        if (empty($reservationDate) || empty($startTime) || empty($endTime)) {
            $errors[] = 'Please provide date and time.';
        }
        
        if (empty($errors)) {
            $start = new DateTime($reservationDate . ' ' . $startTime);
            $end = new DateTime($reservationDate . ' ' . $endTime);
            $hoursReserved = ($end->getTimestamp() - $start->getTimestamp()) / 3600;
            
            if ($hoursReserved <= 0) {
                $errors[] = 'End time must be after start time.';
            } else {
                $totalAmount = $hoursReserved * $hourlyRate;
                $reservationCode = 'RES-' . strtoupper(substr(uniqid(), -8));
                
                // Check for court availability
                $checkStmt = $db->prepare(
                    'SELECT COUNT(*) FROM exclusive_reservations 
                     WHERE court_id = ? AND reservation_date = ? 
                     AND status IN (\'pending\', \'approved\')
                     AND (
                         (start_time < ? AND end_time > ?) OR
                         (start_time < ? AND end_time > ?) OR
                         (start_time >= ? AND end_time <= ?)
                     )'
                );
                $checkStmt->execute([
                    $courtId, $reservationDate,
                    $endTime, $startTime,
                    $endTime, $endTime,
                    $startTime, $endTime
                ]);
                
                if ($checkStmt->fetchColumn() > 0) {
                    $errors[] = 'Court is not available during the selected time.';
                } else {
                    // Create walk-in reservation (user_id = NULL)
                    $stmt = $db->prepare(
                        'INSERT INTO exclusive_reservations 
                         (reservation_code, user_id, customer_name, customer_phone, court_id, reservation_date, start_time, end_time, hours_reserved, hourly_rate, total_amount, payment_method, status)
                         VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'approved\')'
                    );
                    $stmt->execute([
                        $reservationCode, $customerName, $customerPhone, $courtId, $reservationDate, 
                        $startTime, $endTime, $hoursReserved, $hourlyRate, $totalAmount, $paymentMethod
                    ]);
                    $resId = (int) $db->lastInsertId();
                    
                    // Create payment record (marked as paid for walk-in)
                    $db->prepare(
                        'INSERT INTO payments (reservation_id, payment_type, amount, payment_status) 
                         VALUES (?, \'reservation\', ?, \'paid\')'
                    )->execute([$resId, $totalAmount]);
                    
                    flash('success', "Walk-in reservation created for {$customerName}. Code: {$reservationCode}");
                    header('Location: reservations.php');
                    exit;
                }
            }
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                flash('error', $error);
            }
        }
    }
    
    $id = (int) ($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'update_payment') {
        // Update payment status
        $paymentStatus = $_POST['payment_status'] ?? '';
        if (in_array($paymentStatus, ['paid', 'unpaid'], true)) {
            $db->prepare('UPDATE payments SET payment_status = ? WHERE reservation_id = ?')->execute([$paymentStatus, $id]);
            flash('success', 'Payment status updated to ' . $paymentStatus . '.');
        }
    } elseif ($action === 'completed') {
        // Mark reservation as completed
        $db->prepare('UPDATE exclusive_reservations SET status = ?, timer_status = ? WHERE id = ?')->execute(['completed', 'completed', $id]);
        flash('success', 'Reservation marked as completed.');
    } elseif ($action === 'start_timer') {
        // Start timer - record start time
        $db->prepare('UPDATE exclusive_reservations SET timer_started_at = NOW(), timer_status = ? WHERE id = ?')->execute(['running', $id]);
        flash('success', 'Timer started.');
    } elseif ($action === 'stop_timer') {
        // Stop timer
        $db->prepare('UPDATE exclusive_reservations SET timer_status = ? WHERE id = ?')->execute(['stopped', $id]);
        flash('success', 'Timer stopped.');
    } elseif (in_array($action, ['approved', 'rejected'], true)) {
        // Check if payment is paid before approving
        if ($action === 'approved') {
            $payment = $db->prepare('SELECT payment_status FROM payments WHERE reservation_id = ? LIMIT 1');
            $payment->execute([$id]);
            $paymentData = $payment->fetch();
            
            if (!$paymentData || $paymentData['payment_status'] !== 'paid') {
                flash('error', 'Cannot approve reservation. Payment must be marked as "paid" first.');
                header('Location: reservations.php');
                exit;
            }
        }
        
        $db->prepare('UPDATE exclusive_reservations SET status = ? WHERE id = ?')->execute([$action, $id]);
        flash('success', 'Reservation ' . $action . ' successfully.');
    }
    header('Location: reservations.php');
    exit;
}

// Search functionality
$search = $_GET['search'] ?? '';
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = " WHERE (r.reservation_code LIKE ? OR COALESCE(r.customer_name, u.fullname) LIKE ? OR COALESCE(r.customer_phone, u.phone) LIKE ? OR c.court_name LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $searchParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

$reservationsQuery = 'SELECT r.*, 
            COALESCE(r.customer_name, u.fullname) as customer_name,
            COALESCE(r.customer_phone, u.phone) as customer_phone,
            u.email, 
            c.court_name, 
            p.payment_status,
            CASE WHEN r.user_id IS NULL THEN \'walk-in\' ELSE \'online\' END as booking_type,
            EXTRACT(EPOCH FROM (NOW() - r.timer_started_at)) as elapsed_seconds
     FROM exclusive_reservations r
     LEFT JOIN users u ON u.id = r.user_id
     JOIN courts c ON c.id = r.court_id
     LEFT JOIN payments p ON p.reservation_id = r.id' 
     . $searchCondition . '
     ORDER BY r.created_at DESC';

if (!empty($searchParams)) {
    $stmt = $db->prepare($reservationsQuery);
    $stmt->execute($searchParams);
    $reservations = $stmt->fetchAll();
} else {
    $reservations = $db->query($reservationsQuery)->fetchAll();
}

$basePath = '../';
$pageTitle = 'Manage Reservations - Admin';
$currentPage = 'admin';
$adminPage = 'reservations';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header"><h1>Reservation Management</h1><p>Review and approve or reject customer reservations.</p></div>
    <div class="admin-layout">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>
        <div>
            <!-- Walk-in Reservation Form -->
            <div class="card mb-2">
                <div class="card-header"><h3>🆕 Create Walk-in Reservation</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="customer_name">Customer Name *</label>
                                <input type="text" id="customer_name" name="customer_name" required placeholder="John Doe">
                            </div>
                            <div class="form-group">
                                <label for="customer_phone">Phone Number *</label>
                                <input type="tel" id="customer_phone" name="customer_phone" required placeholder="09123456789">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="court_id">Court *</label>
                                <select id="court_id" name="court_id" required>
                                    <option value="">Select Court</option>
                                    <?php foreach ($courts as $court): ?>
                                        <option value="<?= (int) $court['id'] ?>"><?= e($court['court_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="reservation_date">Date *</label>
                                <input type="date" id="reservation_date" name="reservation_date" min="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_time">Start Time *</label>
                                <input type="time" id="start_time" name="start_time" required>
                            </div>
                            <div class="form-group">
                                <label for="end_time">End Time *</label>
                                <input type="time" id="end_time" name="end_time" required>
                            </div>
                            <div class="form-group">
                                <label for="hourly_rate">Hourly Rate (PHP) *</label>
                                <input type="number" id="hourly_rate" name="hourly_rate" value="250" step="0.01" min="1" required>
                            </div>
                            <div class="form-group">
                                <label for="payment_method">Payment Method *</label>
                                <select id="payment_method" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="cashless">Cashless</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="create_walkin" class="btn btn-primary">Create Reservation (Auto-Approved)</button>
                        <p class="form-hint mt-1">Walk-in reservations are automatically approved and marked as paid.</p>
                    </form>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="card">
                <div class="card-header">
                    <h3>All Reservations</h3>
                    <form method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="text" name="search" placeholder="Search by code, customer, phone, or court" 
                               value="<?= e($search) ?>" style="padding: 0.5rem; min-width: 300px; border: 1px solid #ddd; border-radius: 4px;">
                        <button type="submit" class="btn btn-sm btn-primary">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="reservations.php" class="btn btn-sm btn-secondary">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="card-body table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Code</th><th>Type</th><th>Customer</th><th>Court</th><th>Date</th><th>Time</th>
                            <th>Total</th><th>Payment Method</th><th>Payment Status</th><th>Reservation Status</th><th>Timer</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td><?= e($r['reservation_code']) ?></td>
                            <td><span class="badge <?= $r['booking_type'] === 'walk-in' ? 'badge-info' : 'badge-muted' ?>"><?= e($r['booking_type']) ?></span></td>
                            <td>
                                <?= e($r['customer_name']) ?><br>
                                <small><?= e($r['customer_phone']) ?></small>
                                <?php if ($r['email']): ?>
                                    <br><small><?= e($r['email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($r['court_name']) ?></td>
                            <td><?= e(formatDate($r['reservation_date'])) ?></td>
                            <td><?= e(formatTime($r['start_time'])) ?> - <?= e(formatTime($r['end_time'])) ?></td>
                            <td><?= e(formatMoney((float) $r['total_amount'])) ?></td>
                            <td><span class="badge badge-muted"><?= e(ucfirst($r['payment_method'])) ?></span></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="reservation_id" value="<?= (int) $r['id'] ?>">
                                        <select name="payment_status" class="btn btn-sm <?= ($r['payment_status'] ?? 'unpaid') === 'paid' ? 'btn-success' : 'btn-warning' ?>">
                                            <option value="unpaid" <?= ($r['payment_status'] ?? 'unpaid') === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                            <option value="paid" <?= ($r['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                        </select>
                                        <button type="submit" name="action" value="update_payment" class="btn btn-sm btn-primary" style="margin-left:4px;">Update</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge <?= statusBadgeClass($r['payment_status'] ?? 'unpaid') ?>"><?= e($r['payment_status'] ?? 'unpaid') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= e($r['status']) ?></span></td>
                            <td>
                                <?php if ($r['status'] === 'approved'): ?>
                                    <?php 
                                    $timerStatus = $r['timer_status'] ?? 'not_started';
                                    $totalSeconds = $r['hours_reserved'] * 3600;
                                    $elapsedSeconds = ($timerStatus === 'running' && $r['elapsed_seconds']) ? (int)$r['elapsed_seconds'] : 0;
                                    $remainingSeconds = max(0, $totalSeconds - $elapsedSeconds);
                                    ?>
                                    <div class="timer-container" id="timer-<?= (int) $r['id'] ?>" 
                                         data-reservation-id="<?= (int) $r['id'] ?>"
                                         data-total-seconds="<?= $totalSeconds ?>"
                                         data-elapsed-seconds="<?= $elapsedSeconds ?>"
                                         data-timer-status="<?= e($timerStatus) ?>">
                                        <div class="countdown-display" style="font-weight: 600; color: #1a5c2e; margin-bottom: 0.25rem;">
                                            <?php if ($timerStatus === 'running'): ?>
                                                <?php
                                                $h = floor($remainingSeconds / 3600);
                                                $m = floor(($remainingSeconds % 3600) / 60);
                                                $s = $remainingSeconds % 60;
                                                echo sprintf('%02d:%02d:%02d', $h, $m, $s);
                                                ?>
                                            <?php else: ?>
                                                --:--:--
                                            <?php endif; ?>
                                        </div>
                                        <form method="POST" style="display:inline;" class="start-form">
                                            <input type="hidden" name="reservation_id" value="<?= (int) $r['id'] ?>">
                                            <button type="submit" name="action" value="start_timer" 
                                                    class="btn btn-sm btn-success start-btn"
                                                    <?= $timerStatus === 'running' ? 'style="display:none;"' : '' ?>>
                                                Start
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" class="stop-form">
                                            <input type="hidden" name="reservation_id" value="<?= (int) $r['id'] ?>">
                                            <button type="submit" name="action" value="stop_timer" 
                                                    class="btn btn-sm btn-danger stop-btn"
                                                    <?= $timerStatus !== 'running' ? 'style="display:none;"' : '' ?>>
                                                Stop
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="reservation_id" value="<?= (int) $r['id'] ?>">
                                    <?php if (($r['payment_status'] ?? '') === 'paid'): ?>
                                        <button type="submit" name="action" value="approved" class="btn btn-sm btn-primary">Approve</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-primary" disabled title="Payment must be marked as paid first">Approve</button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="rejected" class="btn btn-sm btn-danger">Reject</button>
                                </form>
                                <?php elseif ($r['status'] === 'approved'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="reservation_id" value="<?= (int) $r['id'] ?>">
                                    <button type="submit" name="action" value="completed" class="btn btn-sm btn-success" 
                                            onclick="return confirm('Mark this reservation as completed?')">
                                        ✓ Complete
                                    </button>
                                </form>
                                <?php else: ?>—<?php endif; ?>
                            </td>
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

<script>
// Persistent timer system - timers continue after page reload
const runningTimers = {};

// Initialize all running timers on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.timer-container').forEach(container => {
        const status = container.dataset.timerStatus;
        if (status === 'running') {
            const id = parseInt(container.dataset.reservationId);
            const totalSeconds = parseInt(container.dataset.totalSeconds);
            const elapsedSeconds = parseInt(container.dataset.elapsedSeconds);
            const remainingSeconds = Math.max(0, totalSeconds - elapsedSeconds);
            
            startCountdown(id, remainingSeconds);
        }
    });
});

function startCountdown(reservationId, remainingSeconds) {
    const container = document.getElementById(`timer-${reservationId}`);
    const display = container.querySelector('.countdown-display');
    
    updateDisplay(display, remainingSeconds);
    
    runningTimers[reservationId] = setInterval(() => {
        remainingSeconds--;
        if (remainingSeconds <= 0) {
            clearInterval(runningTimers[reservationId]);
            delete runningTimers[reservationId];
            display.textContent = "TIME'S UP!";
            display.style.color = '#b91c1c';
            display.style.fontWeight = '700';
            playAlert();
        } else {
            updateDisplay(display, remainingSeconds);
            if (remainingSeconds <= 300) display.style.color = '#b45309'; // Orange when <5 mins
        }
    }, 1000);
}

function updateDisplay(display, seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    display.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}

function playAlert() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 800;
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
        osc.start();
        osc.stop(ctx.currentTime + 0.5);
    } catch(e) {}
}

// Clean up intervals when leaving page
window.addEventListener('beforeunload', () => {
    Object.values(runningTimers).forEach(interval => clearInterval(interval));
});
</script>
    let remainingSeconds = totalSeconds;
    updateDisplay(display, remainingSeconds);
    timers[reservationId] = setInterval(() => {
        remainingSeconds--;
        if (remainingSeconds <= 0) {
            clearInterval(timers[reservationId]);
            display.textContent = "TIME'S UP!";
            display.style.color = '#b91c1c';
            display.style.fontWeight = '700';
            stopBtn.textContent = 'Reset';
            playAlert();
        } else {
            updateDisplay(display, remainingSeconds);
            if (remainingSeconds <= 300) display.style.color = '#b45309';
        }
    }, 1000);
}
function stopTimer(reservationId) {
    if (timers[reservationId]) {
        clearInterval(timers[reservationId]);
        delete timers[reservationId];
    }
    const container = document.getElementById(`timer-${reservationId}`);
    const display = container.querySelector('.countdown-display');
    const startBtn = container.querySelector('.start-btn');
    const stopBtn = container.querySelector('.stop-btn');
    display.textContent = '--:--:--';
    display.style.color = '#1a5c2e';
    startBtn.style.display = 'inline-block';
    stopBtn.style.display = 'none';
    stopBtn.textContent = 'Stop';
}
function updateDisplay(display, seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    display.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
}
function playAlert() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 800;
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
        osc.start();
        osc.stop(ctx.currentTime + 0.5);
    } catch(e) {}
}
window.addEventListener('beforeunload', () => Object.keys(timers).forEach(id => clearInterval(timers[id])));
</script>
