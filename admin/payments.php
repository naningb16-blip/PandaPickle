<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();
$admin = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    $paymentId = (int) ($_POST['payment_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = $db->prepare('SELECT * FROM payments WHERE id = ?');
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();

    if ($payment && in_array($action, ['paid', 'rejected'], true)) {
        if ($action === 'paid') {
            $db->prepare(
                'UPDATE payments SET payment_status = \'paid\', verified_by = ?, verified_at = NOW() WHERE id = ?'
            )->execute([$admin['id'], $paymentId]);
            flash('success', 'Payment verified as paid.');
        } else {
            $db->prepare(
                'UPDATE payments SET payment_status = \'rejected\', verified_by = ?, verified_at = NOW() WHERE id = ?'
            )->execute([$admin['id'], $paymentId]);
            flash('success', 'Payment rejected.');
        }
    }
    header('Location: payments.php');
    exit;
}

$payments = $db->query(
    'SELECT p.*,
        u.fullname, u.email,
        r.reservation_code,
        s.title AS session_title
     FROM payments p
     LEFT JOIN exclusive_reservations r ON r.id = p.reservation_id
     LEFT JOIN open_play_registrations reg ON reg.id = p.registration_id
     LEFT JOIN open_play_sessions s ON s.id = reg.session_id
     LEFT JOIN users u ON u.id = COALESCE(r.user_id, reg.user_id)
     ORDER BY p.created_at DESC'
)->fetchAll();

$basePath = '../';
$pageTitle = 'Manage Payments - Admin';
$currentPage = 'admin';
$adminPage = 'payments';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header"><h1>Manage Payments</h1><p>Manual payment verification for reservations and open play.</p></div>
    <div class="admin-layout">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>
        <div class="card">
            <div class="card-body table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th><th>Customer</th><th>Type</th><th>Reference</th>
                            <th>Amount</th><th>Proof</th><th>Status</th><th>Verified</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td>#<?= (int) $p['id'] ?></td>
                            <td><?= e($p['fullname']) ?><br><small><?= e($p['email']) ?></small></td>
                            <td><?= e(str_replace('_', ' ', $p['payment_type'])) ?></td>
                            <td><?= e($p['reservation_code'] ?? $p['session_title'] ?? '—') ?></td>
                            <td><?= e(formatMoney((float) $p['amount'])) ?></td>
                            <td>
                                <?php if ($p['proof_image']): ?>
                                    <img src="<?= e($p['proof_image']) ?>" 
                                         class="proof-thumb" 
                                         alt="Receipt"
                                         onclick="openLightbox('<?= e($p['proof_image']) ?>')"
                                         style="cursor: pointer;">
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><span class="badge <?= statusBadgeClass($p['payment_status']) ?>"><?= e(str_replace('_', ' ', $p['payment_status'])) ?></span></td>
                            <td><?= $p['verified_at'] ? e(formatDate($p['verified_at'])) : '—' ?></td>
                            <td>
                                <?php if ($p['payment_status'] === 'pending_verification'): ?>
                                <form method="POST" style="display:inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" name="action" value="paid" class="btn btn-sm btn-primary">Verify Paid</button>
                                    <button type="submit" name="action" value="rejected" class="btn btn-sm btn-danger">Reject</button>
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

<!-- Lightbox Modal for Receipt Images -->
<div id="receiptLightbox" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999; justify-content:center; align-items:center;" onclick="closeLightbox()">
    <span style="position:absolute; top:20px; right:40px; color:#fff; font-size:40px; font-weight:bold; cursor:pointer;" onclick="closeLightbox()">&times;</span>
    <img id="lightboxImage" src="" style="max-width:90%; max-height:90%; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.5);">
</div>

<script>
function openLightbox(imageUrl) {
    document.getElementById('lightboxImage').src = imageUrl;
    document.getElementById('receiptLightbox').style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}

function closeLightbox() {
    document.getElementById('receiptLightbox').style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
