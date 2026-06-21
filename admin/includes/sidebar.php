<?php
$adminPage = $adminPage ?? '';
?>
<aside class="admin-sidebar">
    <a href="index.php" class="<?= $adminPage === 'dashboard' ? 'active' : '' ?>">📊 Admin Dashboard</a>
    <a href="reservations.php" class="<?= $adminPage === 'reservations' ? 'active' : '' ?>">Reservations</a>
    <a href="open-play.php" class="<?= $adminPage === 'open-play' ? 'active' : '' ?>">Open Play Sessions</a>
    <a href="payments.php" class="<?= $adminPage === 'payments' ? 'active' : '' ?>">💰 Verify Payments</a>
    <a href="payment-settings.php" class="<?= $adminPage === 'payment-settings' ? 'active' : '' ?>">⚙️ Payment Settings</a>
    <a href="../logout.php" style="margin-top: auto; border-top: 1px solid #e8ece9; padding-top: 1rem;">🚪 Logout</a>
</aside>
