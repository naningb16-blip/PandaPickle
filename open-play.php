<?php

require_once __DIR__ . '/includes/auth.php';
requireLogin();

// Redirect admins to admin panel - these pages are for customers only
if (isAdmin()) {
    flash('error', 'Admins should use the Admin panel to manage open play sessions.');
    header('Location: admin/open-play.php');
    exit;
}

$user = getCurrentUser();
$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_session'])) {
    $sessionId = (int) ($_POST['session_id'] ?? 0);
    $matchPreference = $_POST['match_preference'] ?? 'random';

    $stmt = $db->prepare("SELECT * FROM open_play_sessions WHERE id = ? AND status = 'active'");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    if (!$session) {
        $error = 'Session not found or no longer available.';
    } else {
        // 🔒 PREVENT DUPLICATE REGISTRATION: Check if user already registered for this session
        $duplicateCheck = $db->prepare(
            'SELECT COUNT(*) FROM open_play_registrations 
             WHERE user_id = ? AND session_id = ? AND status IN ("pending", "approved")
             AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)'
        );
        $duplicateCheck->execute([$user['id'], $sessionId]);
        
        if ((int) $duplicateCheck->fetchColumn() > 0) {
            $error = 'You are already registered for this session. Please check your registrations below.';
        } elseif ($matchPreference === 'friends') {
            // Play with Friends - Register 4 players (2 teams) at once
            $team1P1 = trim($_POST['team1_player1'] ?? '');
            $team1P2 = trim($_POST['team1_player2'] ?? '');
            $team2P1 = trim($_POST['team2_player1'] ?? '');
            $team2P2 = trim($_POST['team2_player2'] ?? '');
            $paymentMethod = $_POST['payment_method_friends'] ?? 'cash';
            
            if (empty($team1P1) || empty($team1P2) || empty($team2P1) || empty($team2P2)) {
                $error = 'Please enter all 4 player names.';
            } else {
                // Check available spots (need 2 spots for 2 teams)
                $countStmt = $db->prepare(
                    "SELECT COUNT(*) FROM open_play_registrations 
                     WHERE session_id = ? AND status IN ('pending', 'approved')"
                );
                $countStmt->execute([$sessionId]);
                $currentPlayers = (int) $countStmt->fetchColumn();
                
                if ($currentPlayers + 2 > (int) $session['max_players']) {
                    $error = "Not enough space. Need 2 spots available for your group.";
                } else {
                    try {
                        // 🔒 Use transaction to prevent race conditions
                        $db->beginTransaction();

                        $fee = (float) $session['fee_per_player'] * 4; // 4 players total
                        $friendGroup = uniqid('friends_'); // Unique group ID
                        
                        // Register Team 1
                        $regStmt = $db->prepare(
                            'INSERT INTO open_play_registrations (session_id, user_id, user_name, partner_name, match_preference, friend_group, payment_method, total_amount, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'pending\')'
                        );
                        $regStmt->execute([$sessionId, $user['id'], $team1P1, $team1P2, 'friends', $friendGroup, $paymentMethod, (float)$session['fee_per_player'] * 2]);
                        $regId1 = (int) $db->lastInsertId();

                        // Register Team 2
                        $regStmt->execute([$sessionId, $user['id'], $team2P1, $team2P2, 'friends', $friendGroup, $paymentMethod, (float)$session['fee_per_player'] * 2]);
                        $regId2 = (int) $db->lastInsertId();

                        // Create payment records
                        $db->prepare(
                            'INSERT INTO payments (registration_id, payment_type, amount, payment_status)
                             VALUES (?, \'open_play\', ?, \'unpaid\')'
                        )->execute([$regId1, (float)$session['fee_per_player'] * 2]);
                        
                        $db->prepare(
                            'INSERT INTO payments (registration_id, payment_type, amount, payment_status)
                             VALUES (?, \'open_play\', ?, \'unpaid\')'
                        )->execute([$regId2, (float)$session['fee_per_player'] * 2]);

                        $db->commit();

                        flash('success', 'Registered all 4 players! Team 1: ' . $team1P1 . ' & ' . $team1P2 . ' | Team 2: ' . $team2P1 . ' & ' . $team2P2 . '. Total: PHP ' . $fee . '. Awaiting admin approval.');
                        header('Location: open-play.php');
                        exit;
                    } catch (PDOException $e) {
                        $db->rollBack();
                        $error = 'Failed to register. Please try again.';
                        error_log('Open play registration error: ' . $e->getMessage());
                    }
                }
            }
        } else {
            // Random Match - Register 1 team (2 players)
            $userName = trim($_POST['user_name'] ?? '');
            $partnerName = trim($_POST['partner_name'] ?? '');
            $paymentMethod = $_POST['payment_method'] ?? 'cash';

            if (empty($userName)) {
                $error = 'Please enter your name.';
            } elseif (empty($partnerName)) {
                $error = 'Please enter your partner\'s name.';
            } else {
                $countStmt = $db->prepare(
                    "SELECT COUNT(*) FROM open_play_registrations 
                     WHERE session_id = ? AND status IN ('pending', 'approved')"
                );
                $countStmt->execute([$sessionId]);
                $currentPlayers = (int) $countStmt->fetchColumn();
                
                if ($currentPlayers >= (int) $session['max_players']) {
                    $error = "Session is full. Maximum " . (int) $session['max_players'] . " players allowed.";
                } else {
                    try {
                        // 🔒 Use transaction to prevent race conditions
                        $db->beginTransaction();

                        $fee = (float) $session['fee_per_player'] * 2; // 2 players per team

                        $regStmt = $db->prepare(
                            'INSERT INTO open_play_registrations (session_id, user_id, user_name, partner_name, match_preference, friend_group, payment_method, total_amount, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'pending\')'
                        );
                        $regStmt->execute([$sessionId, $user['id'], $userName, $partnerName, 'random', null, $paymentMethod, $fee]);
                        $regId = (int) $db->lastInsertId();

                        $db->prepare(
                            'INSERT INTO payments (registration_id, payment_type, amount, payment_status)
                             VALUES (?, \'open_play\', ?, \'unpaid\')'
                        )->execute([$regId, $fee]);

                        $db->commit();

                        flash('success', 'Registered for ' . $session['title'] . '! Team: ' . $userName . ' & ' . $partnerName . '. Total: ' . formatMoney($fee) . '. Awaiting admin approval.');
                        header('Location: open-play.php');
                        exit;
                    } catch (PDOException $e) {
                        $db->rollBack();
                        $error = 'Failed to register. Please try again.';
                        error_log('Open play registration error: ' . $e->getMessage());
                    }
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_proof'])) {
    flash('error', 'Payment proof upload is no longer required. Please wait for admin approval.');
    header('Location: open-play.php');
    exit;
}

$sessions = $db->query(
    "SELECT s.*,
        (SELECT COUNT(*) FROM open_play_registrations
         WHERE session_id = s.id AND status IN ('pending', 'approved')) AS current_players
     FROM open_play_sessions s
     WHERE s.status = 'active' AND s.session_date >= CURDATE()
     ORDER BY s.session_date, s.start_time"
)->fetchAll();

$myRegistrations = $db->prepare(
    'SELECT reg.*, s.title, s.session_date, s.start_time, s.end_time, p.payment_status
     FROM open_play_registrations reg
     JOIN open_play_sessions s ON s.id = reg.session_id
     LEFT JOIN payments p ON p.registration_id = reg.id
     WHERE reg.user_id = ?
     ORDER BY reg.created_at DESC'
);
$myRegistrations->execute([$user['id']]);
$myRegistrations = $myRegistrations->fetchAll();

$pageTitle = 'Open Play - PandaPickle';
$currentPage = 'open-play';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Open Play Sessions</h1>
        <p>PHP 50 per player (PHP 100 per team) &bull; Doubles format (4 players per match)</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card mb-2">
        <div class="card-header"><h3>Available Sessions</h3></div>
        <div class="card-body table-wrap">
            <?php if (empty($sessions)): ?>
                <p class="text-muted">No open play sessions available at the moment.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Session</th><th>Date</th><th>Time</th>
                            <th>Players</th><th>Fee/Team</th><th>Status</th><th>Register</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $s):
                            $available = (int) $s['max_players'] - (int) $s['current_players'];
                        ?>
                        <tr>
                            <td><strong><?= e($s['title']) ?></strong></td>
                            <td><?= e(formatDate($s['session_date'])) ?></td>
                            <td><?= e(formatTime($s['start_time'])) ?> - <?= e(formatTime($s['end_time'])) ?></td>
                            <td><?= (int) $s['current_players'] ?> / <?= (int) $s['max_players'] ?> (<?= $available ?> open)</td>
                            <td><?= e(formatMoney((float) $s['fee_per_player'] * 2)) ?></td>
                            <td><span class="badge badge-success"><?= e($s['status']) ?></span></td>
                            <td>
                                <?php if ($available > 0): ?>
                                <form method="POST" style="display:flex;gap:0.5rem;align-items:flex-start;flex-wrap:wrap;flex-direction:column;">
                                    <input type="hidden" name="session_id" value="<?= (int) $s['id'] ?>">
                                    
                                    <div style="display:flex;gap:1rem;align-items:center;font-size:0.9rem;flex-wrap:wrap;margin-bottom:0.5rem;">
                                        <label style="display:flex;align-items:center;gap:0.3rem;cursor:pointer;">
                                            <input type="radio" name="match_preference" value="random" checked onclick="document.getElementById('random_fields_<?= $s['id'] ?>').style.display='flex'; document.getElementById('friends_fields_<?= $s['id'] ?>').style.display='none';">
                                            <span>Random Match (PHP 100)</span>
                                        </label>
                                        <label style="display:flex;align-items:center;gap:0.3rem;cursor:pointer;">
                                            <input type="radio" name="match_preference" value="friends" onclick="document.getElementById('random_fields_<?= $s['id'] ?>').style.display='none'; document.getElementById('friends_fields_<?= $s['id'] ?>').style.display='flex';">
                                            <span>Play with Friends (PHP 200 - all 4 players)</span>
                                        </label>
                                    </div>
                                    
                                    <!-- Random Match Fields (default) -->
                                    <div id="random_fields_<?= $s['id'] ?>" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                        <input type="text" name="user_name" placeholder="Your Name" required style="padding:0.4rem;min-width:150px;">
                                        <input type="text" name="partner_name" placeholder="Partner's Name" required style="padding:0.4rem;min-width:150px;">
                                        <select name="payment_method" required style="padding:0.4rem;">
                                            <option value="cash">Cash</option>
                                            <option value="cashless">Cashless</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Play with Friends Fields (4 players) -->
                                    <div id="friends_fields_<?= $s['id'] ?>" style="display:none;gap:0.5rem;flex-wrap:wrap;flex-direction:column;border:2px solid #059669;padding:1rem;border-radius:8px;background:#f0fdf4;">
                                        <strong style="color:#059669;">Register All 4 Players (2 Teams):</strong>
                                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                            <input type="text" name="team1_player1" placeholder="Your Name" style="padding:0.4rem;min-width:150px;">
                                            <input type="text" name="team1_player2" placeholder="Your Partner" style="padding:0.4rem;min-width:150px;">
                                        </div>
                                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                                            <input type="text" name="team2_player1" placeholder="Friend 1 Name" style="padding:0.4rem;min-width:150px;">
                                            <input type="text" name="team2_player2" placeholder="Friend 2 Name" style="padding:0.4rem;min-width:150px;">
                                        </div>
                                        <select name="payment_method_friends" style="padding:0.4rem;">
                                            <option value="cash">Cash</option>
                                            <option value="cashless">Cashless</option>
                                        </select>
                                        <p style="font-size:0.85rem;color:#065f46;margin:0.5rem 0 0 0;">
                                            💰 Total: PHP 200 for all 4 players<br>
                                            ✅ You'll play together immediately - no waiting for random matching!
                                        </p>
                                    </div>
                                    
                                    <button type="submit" name="register_session" class="btn btn-sm btn-primary">Register</button>
                                </form>
                                <?php else: ?>
                                    <span class="badge badge-danger">Full</span>
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
        <div class="card-header"><h3>My Registrations</h3></div>
        <div class="card-body table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Session</th><th>Date</th><th>Team Players</th><th>Payment Method</th><th>Total</th>
                        <th>Payment Status</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myRegistrations as $reg): ?>
                    <tr>
                        <td><?= e($reg['title']) ?></td>
                        <td><?= e(formatDate($reg['session_date'])) ?></td>
                        <td>
                            <strong><?= e($reg['user_name'] ?: 'You') ?></strong>
                            &
                            <strong><?= e($reg['partner_name']) ?></strong>
                        </td>
                        <td><span class="badge badge-muted"><?= e(ucfirst($reg['payment_method'])) ?></span></td>
                        <td><?= e(formatMoney((float) $reg['total_amount'])) ?></td>
                        <td><span class="badge <?= statusBadgeClass($reg['payment_status'] ?? 'unpaid') ?>"><?= e($reg['payment_status'] ?? 'unpaid') ?></span></td>
                        <td><span class="badge <?= statusBadgeClass($reg['status']) ?>"><?= e($reg['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
