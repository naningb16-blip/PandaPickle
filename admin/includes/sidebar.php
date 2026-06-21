<?php
$adminPage = $adminPage ?? '';
?>
<aside class="admin-sidebar">
    <a href="index.php" class="<?= $adminPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
    <a href="reservations.php" class="<?= $adminPage === 'reservations' ? 'active' : '' ?>">Reservations</a>
    <a href="open-play.php" class="<?= $adminPage === 'open-play' ? 'active' : '' ?>">Open Play Sessions</a>
    <a href="payment-settings.php" class="<?= $adminPage === 'payment-settings' ? 'active' : '' ?>">💳 Payment Settings</a>
    <a href="../dashboard.php">← Back to Site</a>
</aside>
