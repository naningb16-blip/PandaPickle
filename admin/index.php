<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Get quick stats
$pendingReservations = (int) $db->query("SELECT COUNT(*) FROM exclusive_reservations WHERE status = 'pending'")->fetchColumn();
$pendingRegistrations = (int) $db->query("SELECT COUNT(*) FROM open_play_registrations WHERE status = 'pending'")->fetchColumn();
$activeSessions = (int) $db->query("SELECT COUNT(*) FROM open_play_sessions WHERE status = 'active' AND session_date >= CURRENT_DATE")->fetchColumn();

$basePath = '../';
$pageTitle = 'Admin Dashboard';
$currentPage = 'admin';
$adminPage = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Manage reservations and open play sessions</p>
    </div>

    <div class="admin-layout">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <div>
            <div class="grid-3 mb-2">
                <div class="stat-card">
                    <div class="value"><?= $pendingReservations ?></div>
                    <div class="label">Pending Reservations</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?= $pendingRegistrations ?></div>
                    <div class="label">Pending Open Play</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?= $activeSessions ?></div>
                    <div class="label">Active Sessions</div>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-header"><h3>Quick Actions</h3></div>
                    <div class="card-body">
                        <a href="reservations.php" class="btn btn-primary btn-block mb-1">Manage Reservations</a>
                        <a href="open-play.php" class="btn btn-primary btn-block">Manage Open Play Sessions</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Admin Guide</h3></div>
                    <div class="card-body text-muted">
                        <p><strong>Reservations:</strong></p>
                        <ul style="margin-left: 1.5rem;">
                            <li>Update payment status</li>
                            <li>Approve when paid</li>
                            <li>Reject anytime</li>
                        </ul>
                        <p class="mt-1"><strong>Open Play:</strong></p>
                        <ul style="margin-left: 1.5rem;">
                            <li>Create sessions</li>
                            <li>Approve registrations</li>
                            <li>Generate matches</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
