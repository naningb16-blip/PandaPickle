<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Get quick stats (includes walk-ins since no user_id filter)
$pendingReservations = (int) $db->query("SELECT COUNT(*) FROM exclusive_reservations WHERE status = 'pending'")->fetchColumn();
$pendingRegistrations = (int) $db->query("SELECT COUNT(*) FROM open_play_registrations WHERE status = 'pending'")->fetchColumn();
$activeSessions = (int) $db->query("SELECT COUNT(*) FROM open_play_sessions WHERE status = 'active' AND session_date >= CURRENT_DATE")->fetchColumn();

// Additional stats including walk-ins
$upcomingReservations = (int) $db->query("SELECT COUNT(*) FROM exclusive_reservations WHERE reservation_date >= CURRENT_DATE AND status IN ('pending', 'approved')")->fetchColumn();
$totalBookings = (int) $db->query("SELECT COUNT(*) FROM exclusive_reservations")->fetchColumn();
$walkinCount = (int) $db->query("SELECT COUNT(*) FROM exclusive_reservations WHERE user_id IS NULL")->fetchColumn();

// Get recent reservations (all types)
$recentReservations = $db->query(
    "SELECT r.*, 
            COALESCE(r.customer_name, u.fullname) as customer_name,
            c.court_name,
            CASE WHEN r.user_id IS NULL THEN 'walk-in' ELSE 'online' END as booking_type
     FROM exclusive_reservations r
     LEFT JOIN users u ON u.id = r.user_id
     JOIN courts c ON c.id = r.court_id
     ORDER BY r.created_at DESC
     LIMIT 10"
)->fetchAll();

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

            <div class="grid-3 mb-2">
                <div class="stat-card">
                    <div class="value"><?= $upcomingReservations ?></div>
                    <div class="label">Upcoming Reservations</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?= $totalBookings ?></div>
                    <div class="label">Total Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?= $walkinCount ?></div>
                    <div class="label">Walk-in Reservations</div>
                </div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-header"><h3>Recent Reservations</h3></div>
                    <div class="card-body table-wrap">
                        <?php if (empty($recentReservations)): ?>
                            <p class="text-muted">No reservations yet.</p>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Type</th>
                                        <th>Customer</th>
                                        <th>Court</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentReservations as $r): ?>
                                    <tr>
                                        <td><?= e($r['reservation_code']) ?></td>
                                        <td><span class="badge <?= $r['booking_type'] === 'walk-in' ? 'badge-info' : 'badge-muted' ?>"><?= e($r['booking_type']) ?></span></td>
                                        <td><?= e($r['customer_name']) ?></td>
                                        <td><?= e($r['court_name']) ?></td>
                                        <td><?= e(formatDate($r['reservation_date'])) ?></td>
                                        <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= e($r['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Quick Actions</h3></div>
                    <div class="card-body">
                        <a href="reservations.php" class="btn btn-primary btn-block mb-1">Manage Reservations</a>
                        <a href="open-play.php" class="btn btn-primary btn-block mb-1">Manage Open Play Sessions</a>
                        <a href="reports.php" class="btn btn-secondary btn-block">View Reports</a>
                    </div>
                    
                    <div class="card-header mt-2"><h3>Admin Guide</h3></div>
                    <div class="card-body text-muted">
                        <p><strong>Reservations:</strong></p>
                        <ul style="margin-left: 1.5rem;">
                            <li>Create walk-in bookings</li>
                            <li>Update payment status</li>
                            <li>Approve when paid</li>
                        </ul>
                        <p class="mt-1"><strong>Open Play:</strong></p>
                        <ul style="margin-left: 1.5rem;">
                            <li>Create sessions</li>
                            <li>Generate matches</li>
                            <li>Walk-in registration</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
