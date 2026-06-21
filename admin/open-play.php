<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();
$fbPost = null;

// Get all active sessions for the walk-in form
$activeSessions = $db->query(
    'SELECT id, title, session_date, start_time, end_time, max_players, fee_per_player,
        (SELECT COUNT(*) FROM open_play_registrations
         WHERE session_id = open_play_sessions.id AND status IN (\'pending\', \'approved\')) AS current_players
     FROM open_play_sessions 
     WHERE status = \'active\' AND session_date >= CURRENT_DATE
     ORDER BY session_date, start_time'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    // Handle walk-in registration
    if (isset($_POST['create_walkin'])) {
        $sessionId = (int) ($_POST['session_id'] ?? 0);
        $matchPreference = $_POST['match_preference'] ?? 'random';
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        
        $errors = [];
        
        if (empty($contactPhone)) {
            $errors[] = 'Contact phone is required.';
        }
        if ($sessionId <= 0) {
            $errors[] = 'Please select a valid session.';
        }
        
        if (empty($errors)) {
            // Get session details
            $sessionStmt = $db->prepare('SELECT * FROM open_play_sessions WHERE id = ? AND status = \'active\'');
            $sessionStmt->execute([$sessionId]);
            $session = $sessionStmt->fetch();
            
            if (!$session) {
                $errors[] = 'Session not found or no longer available.';
            } else {
                $countStmt = $db->prepare(
                    'SELECT COUNT(*) FROM open_play_registrations 
                     WHERE session_id = ? AND status IN (\'pending\', \'approved\')'
                );
                $countStmt->execute([$sessionId]);
                $currentPlayers = (int) $countStmt->fetchColumn();
                
                if ($matchPreference === 'friends') {
                    // Play with Friends - Register 4 players (2 teams) at once
                    $team1P1 = trim($_POST['team1_player1'] ?? '');
                    $team1P2 = trim($_POST['team1_player2'] ?? '');
                    $team2P1 = trim($_POST['team2_player1'] ?? '');
                    $team2P2 = trim($_POST['team2_player2'] ?? '');
                    
                    if (empty($team1P1) || empty($team1P2) || empty($team2P1) || empty($team2P2)) {
                        $errors[] = 'Please enter all 4 player names.';
                    } elseif ($currentPlayers + 2 > (int) $session['max_players']) {
                        $errors[] = "Not enough space. Need 2 spots available for 4 players.";
                    } else {
                        $friendGroup = uniqid('walkin_friends_'); // Unique group ID
                        $feePerTeam = (float) $session['fee_per_player'] * 2;
                        
                        // Register Team 1
                        $regStmt = $db->prepare(
                            'INSERT INTO open_play_registrations (session_id, user_id, user_name, partner_name, contact_phone, match_preference, friend_group, payment_method, total_amount, status)
                             VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, "approved")'
                        );
                        $regStmt->execute([$sessionId, $team1P1, $team1P2, $contactPhone, 'friends', $friendGroup, $paymentMethod, $feePerTeam]);
                        $regId1 = (int) $db->lastInsertId();

                        // Register Team 2
                        $regStmt->execute([$sessionId, $team2P1, $team2P2, $contactPhone, 'friends', $friendGroup, $paymentMethod, $feePerTeam]);
                        $regId2 = (int) $db->lastInsertId();

                        // Create payment records (marked as paid for walk-in)
                        $db->prepare(
                            'INSERT INTO payments (registration_id, payment_type, amount, payment_status)
                             VALUES (?, "open_play", ?, "paid")'
                        )->execute([$regId1, $feePerTeam]);
                        
                        $db->prepare(
                            'INSERT INTO payments (registration_id, payment_type, amount, payment_status)
                             VALUES (?, "open_play", ?, "paid")'
                        )->execute([$regId2, $feePerTeam]);

                        flash('success', "Walk-in registration created for all 4 players! Team 1: {$team1P1} & {$team1P2} | Team 2: {$team2P1} & {$team2P2}. Total: PHP " . ($feePerTeam * 2) . " paid.");
                        header('Location: open-play.php');
                        exit;
                    }
                } else {
                    // Random Match - Register 1 team (2 players)
                    $userName = trim($_POST['user_name'] ?? '');
                    $partnerName = trim($_POST['partner_name'] ?? '');
                    
                    if (empty($userName)) {
                        $errors[] = 'Player name is required.';
                    } elseif (empty($partnerName)) {
                        $errors[] = 'Partner name is required.';
                    } elseif ($currentPlayers >= (int) $session['max_players']) {
                        $errors[] = "Session is full. Maximum " . (int) $session['max_players'] . " players allowed.";
                    } else {
                        $fee = (float) $session['fee_per_player'] * 2; // 2 players per team
                        
                        // Create walk-in registration (user_id = NULL, auto-approved)
                        $regStmt = $db->prepare(
                            'INSERT INTO open_play_registrations (session_id, user_id, user_name, partner_name, contact_phone, match_preference, friend_group, payment_method, total_amount, status)
                             VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, "approved")'
                        );
                        $regStmt->execute([$sessionId, $userName, $partnerName, $contactPhone, 'random', null, $paymentMethod, $fee]);
                        $regId = (int) $db->lastInsertId();

                        // Create payment record (marked as paid for walk-in)
                        $db->prepare(
                            'INSERT INTO payments (registration_id, payment_type, amount, payment_status)
                             VALUES (?, "open_play", ?, "paid")'
                        )->execute([$regId, $fee]);

                        flash('success', "Walk-in registration created for {$userName} & {$partnerName}. Team registered and paid.");
                        header('Location: open-play.php');
                        exit;
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                flash('error', $error);
            }
        }
    }
    
    if (isset($_POST['create_session'])) {
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['session_date'] ?? '';
        $start = date('H:i:s', strtotime($_POST['start_time'] ?? ''));
        $end = date('H:i:s', strtotime($_POST['end_time'] ?? ''));
        $maxPlayers = (int) ($_POST['max_players'] ?? 20);
        $fee = (float) ($_POST['fee_per_player'] ?? OPEN_PLAY_FEE);

        if ($title && $date && $start < $end) {
            $stmt = $db->prepare(
                'INSERT INTO open_play_sessions (title, session_date, start_time, end_time, max_players, fee_per_player, status)
                 VALUES (?, ?, ?, ?, ?, ?, \'active\')'
            );
            $stmt->execute([$title, $date, $start, $end, $maxPlayers, $fee]);
            $sessionId = (int) $db->lastInsertId();
            $sessionStmt = $db->prepare('SELECT * FROM open_play_sessions WHERE id = ?');
            $sessionStmt->execute([$sessionId]);
            $session = $sessionStmt->fetch();
            $fbPost = generateFacebookPost($session);
            flash('success', 'Open play session created!');
        }
    }

    if (isset($_POST['cancel_session'])) {
        $db->prepare('UPDATE open_play_sessions SET status = \'cancelled\' WHERE id = ?')
            ->execute([(int) $_POST['session_id']]);
        flash('success', 'Session cancelled.');
        header('Location: open-play.php');
        exit;
    }

    if (isset($_POST['finish_session'])) {
        $db->prepare('UPDATE open_play_sessions SET status = \'completed\' WHERE id = ?')
            ->execute([(int) $_POST['session_id']]);
        flash('success', 'Session marked as completed.');
        header('Location: open-play.php');
        exit;
    }

    if (isset($_POST['update_payment'])) {
        $regId = (int) ($_POST['registration_id'] ?? 0);
        $paymentStatus = $_POST['payment_status'] ?? '';
        if (in_array($paymentStatus, ['paid', 'unpaid'], true)) {
            $db->prepare('UPDATE payments SET payment_status = ? WHERE registration_id = ?')->execute([$paymentStatus, $regId]);
            flash('success', 'Payment status updated to ' . $paymentStatus . '.');
        }
        header('Location: open-play.php');
        exit;
    }

    if (isset($_POST['approve_registration'])) {
        $regId = (int) $_POST['registration_id'];
        
        // Check payment status
        $payment = $db->prepare('SELECT payment_status FROM payments WHERE registration_id = ? LIMIT 1');
        $payment->execute([$regId]);
        $paymentData = $payment->fetch();
        
        if (!$paymentData || $paymentData['payment_status'] !== 'paid') {
            flash('error', 'Cannot approve registration. Payment must be marked as "paid" first.');
        } else {
            $db->prepare('UPDATE open_play_registrations SET status = \'approved\' WHERE id = ?')
                ->execute([$regId]);
            flash('success', 'Registration approved.');
        }
        header('Location: open-play.php');
        exit;
    }

    if (isset($_POST['reject_registration'])) {
        $db->prepare('UPDATE open_play_registrations SET status = \'rejected\' WHERE id = ?')
            ->execute([(int) $_POST['registration_id']]);
        flash('success', 'Registration rejected.');
        header('Location: open-play.php');
        exit;
    }

    if (isset($_POST['finish_match'])) {
        $matchId = (int) $_POST['match_id'];
        $db->prepare('UPDATE open_play_matches SET match_status = \'completed\' WHERE id = ?')
            ->execute([$matchId]);
        flash('success', 'Match marked as completed.');
        header('Location: open-play.php');
        exit;
    }

    if (isset($_POST['reset_match'])) {
        $matchId = (int) $_POST['match_id'];
        $db->prepare('UPDATE open_play_matches SET match_status = \'pending\' WHERE id = ?')
            ->execute([$matchId]);
        flash('success', 'Match reset to pending.');
        header('Location: open-play.php');
        exit;
    }

    if (isset($_POST['generate_matches'])) {
        $sessionId = (int) $_POST['session_id'];
        
        // Get current round number
        $roundStmt = $db->prepare('SELECT COALESCE(MAX(match_round), 0) + 1 as next_round FROM open_play_matches WHERE session_id = ?');
        $roundStmt->execute([$sessionId]);
        $nextRound = (int) $roundStmt->fetchColumn();
        
        // Get approved registrations with friend group info
        $regsStmt = $db->prepare(
            'SELECT reg.id, reg.match_preference, reg.friend_group FROM open_play_registrations reg
             WHERE reg.session_id = ? AND reg.status = \'approved\'
             AND reg.id NOT IN (
                 SELECT player1_reg_id FROM open_play_matches WHERE session_id = ? AND match_round = ? AND match_status = \'completed\'
                 UNION SELECT player2_reg_id FROM open_play_matches WHERE session_id = ? AND match_round = ? AND match_status = \'completed\'
                 UNION SELECT player3_reg_id FROM open_play_matches WHERE session_id = ? AND match_round = ? AND match_status = \'completed\'
                 UNION SELECT player4_reg_id FROM open_play_matches WHERE session_id = ? AND match_round = ? AND match_status = \'completed\'
             )
             ORDER BY reg.match_preference DESC, reg.friend_group, RANDOM()'
        );
        $regsStmt->execute([$sessionId, $sessionId, $nextRound - 1, $sessionId, $nextRound - 1, $sessionId, $nextRound - 1, $sessionId, $nextRound - 1]);
        $registrations = $regsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group teams by friend_group
        $friendGroups = [];
        $randomTeams = [];
        
        foreach ($registrations as $reg) {
            if ($reg['match_preference'] === 'friends' && !empty($reg['friend_group'])) {
                $groupName = $reg['friend_group'];
                if (!isset($friendGroups[$groupName])) {
                    $friendGroups[$groupName] = [];
                }
                $friendGroups[$groupName][] = $reg['id'];
            } else {
                $randomTeams[] = $reg['id'];
            }
        }
        
        $matchesCreated = 0;
        $gameNumber = 1;
        
        // Match teams within each friend group
        foreach ($friendGroups as $groupName => $teamIds) {
            for ($i = 0; $i < count($teamIds) - 1; $i += 2) {
                $stmt = $db->prepare(
                    'INSERT INTO open_play_matches (session_id, match_round, game_number, player1_reg_id, player2_reg_id, player3_reg_id, player4_reg_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$sessionId, $nextRound, $gameNumber, $teamIds[$i], $teamIds[$i], $teamIds[$i+1], $teamIds[$i+1]]);
                $matchesCreated++;
                $gameNumber++;
            }
            
            // Add odd team to random pool
            if (count($teamIds) % 2 === 1) {
                $randomTeams[] = $teamIds[count($teamIds) - 1];
            }
        }
        
        // Match random teams
        for ($i = 0; $i < count($randomTeams) - 1; $i += 2) {
            $stmt = $db->prepare(
                'INSERT INTO open_play_matches (session_id, match_round, game_number, player1_reg_id, player2_reg_id, player3_reg_id, player4_reg_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$sessionId, $nextRound, $gameNumber, $randomTeams[$i], $randomTeams[$i], $randomTeams[$i+1], $randomTeams[$i+1]]);
            $matchesCreated++;
            $gameNumber++;
        }
        
        if ($matchesCreated > 0) {
            flash('success', "Created {$matchesCreated} matches for Round {$nextRound}. Friend groups matched together, then random teams!");
        } else {
            flash('error', 'Not enough approved registrations. Need at least 2 teams to create matches.');
        }
        header('Location: open-play.php');
        exit;
    }
}

$sessions = $db->query(
    'SELECT s.*,
        (SELECT COUNT(*) FROM open_play_registrations
         WHERE session_id = s.id AND status IN (\'pending\', \'approved\')) AS current_players
     FROM open_play_sessions s ORDER BY s.session_date DESC, s.start_time DESC'
)->fetchAll();

$registrations = $db->query(
    'SELECT reg.*, 
            COALESCE(reg.user_name, u.fullname) as display_name,
            COALESCE(reg.contact_phone, u.phone) as display_phone,
            u.email, 
            s.title, 
            p.payment_status,
            CASE WHEN reg.user_id IS NULL THEN \'walk-in\' ELSE \'online\' END as booking_type
     FROM open_play_registrations reg
     LEFT JOIN users u ON u.id = reg.user_id
     JOIN open_play_sessions s ON s.id = reg.session_id
     LEFT JOIN payments p ON p.registration_id = reg.id
     ORDER BY reg.created_at DESC'
)->fetchAll();

// Get friend groups with their match assignments and game numbers
$friendGroupsQuery = "
    SELECT 
        s.title as session_title,
        s.session_date,
        reg.friend_group,
        reg.match_preference,
        COUNT(DISTINCT reg.id) as team_count,
        STRING_AGG(DISTINCT COALESCE(reg.user_name, u.fullname) || ' & ' || reg.partner_name, ' | ') as teams,
        MIN(m.game_number) as first_game,
        MAX(m.game_number) as last_game,
        COUNT(DISTINCT m.id) as matches_count
    FROM open_play_registrations reg
    LEFT JOIN users u ON u.id = reg.user_id
    JOIN open_play_sessions s ON s.id = reg.session_id
    LEFT JOIN open_play_matches m ON (
        m.player1_reg_id = reg.id OR 
        m.player2_reg_id = reg.id OR 
        m.player3_reg_id = reg.id OR 
        m.player4_reg_id = reg.id
    )
    WHERE reg.match_preference = 'friends' 
    AND reg.friend_group IS NOT NULL 
    AND reg.friend_group != ''
    AND reg.status = 'approved'
    GROUP BY s.id, s.title, s.session_date, reg.friend_group, reg.match_preference
    ORDER BY s.session_date DESC, reg.friend_group
";
$friendGroups = $db->query($friendGroupsQuery)->fetchAll();

// Get matches for active sessions
$filterRound = isset($_GET['filter_round']) ? (int)$_GET['filter_round'] : 0;
$filterSession = isset($_GET['filter_session']) ? (int)$_GET['filter_session'] : 0;

// Build the matches query with filters
$matchesQuery = 'SELECT m.*, s.title as session_title,
        r1.user_name as p1_name, r1.partner_name as p1_partner,
        r2.user_name as p2_name, r2.partner_name as p2_partner,
        r3.user_name as p3_name, r3.partner_name as p3_partner,
        r4.user_name as p4_name, r4.partner_name as p4_partner,
        u1.fullname as p1_account, u2.fullname as p2_account,
        u3.fullname as p3_account, u4.fullname as p4_account
     FROM open_play_matches m
     JOIN open_play_sessions s ON s.id = m.session_id
     JOIN open_play_registrations r1 ON r1.id = m.player1_reg_id
     JOIN open_play_registrations r2 ON r2.id = m.player2_reg_id
     JOIN open_play_registrations r3 ON r3.id = m.player3_reg_id
     JOIN open_play_registrations r4 ON r4.id = m.player4_reg_id
     LEFT JOIN users u1 ON u1.id = r1.user_id
     LEFT JOIN users u2 ON u2.id = r2.user_id
     LEFT JOIN users u3 ON u3.id = r3.user_id
     LEFT JOIN users u4 ON u4.id = r4.user_id';

$conditions = [];
$params = [];

if ($filterSession > 0) {
    $conditions[] = 'm.session_id = ?';
    $params[] = $filterSession;
}

if ($filterRound > 0) {
    $conditions[] = 'm.match_round = ?';
    $params[] = $filterRound;
}

if (!empty($conditions)) {
    $matchesQuery .= ' WHERE ' . implode(' AND ', $conditions);
}

$matchesQuery .= ' ORDER BY m.session_id DESC, m.match_round DESC, m.game_number ASC, m.id DESC';

if (!empty($params)) {
    $matchesStmt = $db->prepare($matchesQuery);
    $matchesStmt->execute($params);
    $matches = $matchesStmt->fetchAll();
} else {
    $matches = $db->query($matchesQuery)->fetchAll();
}

// Get available rounds for filter dropdown
$rounds = $db->query('SELECT DISTINCT match_round FROM open_play_matches ORDER BY match_round DESC')->fetchAll(PDO::FETCH_COLUMN);

// Get sessions with matches for filter dropdown
$sessionsWithMatches = $db->query('SELECT DISTINCT s.id, s.title, s.session_date FROM open_play_sessions s JOIN open_play_matches m ON m.session_id = s.id ORDER BY s.session_date DESC')->fetchAll();

$basePath = '../';
$pageTitle = 'Open Play Management - Admin';
$currentPage = 'admin';
$adminPage = 'open-play';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header"><h1>Manage Open Play Sessions</h1></div>
    <div class="admin-layout">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>
        <div>
            <div class="card mb-2">
                <div class="card-header"><h3>Create Open Play Session</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" name="title" required placeholder="Friday Night Open Play">
                            </div>
                            <div class="form-group">
                                <label for="session_date">Session Date</label>
                                <input type="date" id="session_date" name="session_date" min="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_time">Start Time</label>
                                <input type="time" id="start_time" name="start_time" required>
                            </div>
                            <div class="form-group">
                                <label for="end_time">End Time</label>
                                <input type="time" id="end_time" name="end_time" required>
                            </div>
                            <div class="form-group">
                                <label for="max_players">Max Players</label>
                                <input type="number" id="max_players" name="max_players" min="2" value="20" required>
                            </div>
                            <div class="form-group">
                                <label for="fee_per_player">Fee per Player (PHP)</label>
                                <input type="number" id="fee_per_player" name="fee_per_player" min="1" value="50" step="0.01" required>
                            </div>
                        </div>
                        <button type="submit" name="create_session" class="btn btn-primary">Create Session</button>
                    </form>
                </div>
            </div>

            <?php if ($fbPost): ?>
            <div class="card mb-2">
                <div class="card-header"><h3>Facebook Announcement (Copy & Paste)</h3></div>
                <div class="card-body">
                    <textarea class="fb-post-box" readonly onclick="this.select()"><?= e($fbPost) ?></textarea>
                    <p class="form-hint mt-1">Click the text box to select all, then copy to Facebook.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Walk-in Registration Form -->
            <div class="card mb-2">
                <div class="card-header"><h3>🆕 Walk-in Player Registration</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="walkin_session_id">Session *</label>
                                <select id="walkin_session_id" name="session_id" required>
                                    <option value="">Select Session</option>
                                    <?php foreach ($activeSessions as $sess): 
                                        $available = (int) $sess['max_players'] - (int) $sess['current_players'];
                                    ?>
                                        <option value="<?= (int) $sess['id'] ?>" <?= $available <= 0 ? 'disabled' : '' ?>>
                                            <?= e($sess['title']) ?> - <?= e(formatDate($sess['session_date'])) ?>
                                            (<?= $available ?> spots left)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="walkin_contact_phone">Contact Phone *</label>
                                <input type="tel" id="walkin_contact_phone" name="contact_phone" required placeholder="09123456789">
                            </div>
                            <div class="form-group">
                                <label for="walkin_payment_method">Payment Method *</label>
                                <select id="walkin_payment_method" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="cashless">Cashless</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Match Preference *</label>
                            <div style="display:flex;gap:1rem;margin-top:0.5rem;">
                                <label style="display:flex;align-items:center;gap:0.3rem;cursor:pointer;">
                                    <input type="radio" name="match_preference" value="random" checked onclick="document.getElementById('walkin_random_fields').style.display='flex'; document.getElementById('walkin_friends_fields').style.display='none';">
                                    <span>Random Match (PHP 100)</span>
                                </label>
                                <label style="display:flex;align-items:center;gap:0.3rem;cursor:pointer;">
                                    <input type="radio" name="match_preference" value="friends" onclick="document.getElementById('walkin_random_fields').style.display='none'; document.getElementById('walkin_friends_fields').style.display='flex';">
                                    <span>Play with Friends (PHP 200 - all 4 players)</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Random Match Fields (default) -->
                        <div id="walkin_random_fields" class="form-row" style="display:flex;">
                            <div class="form-group">
                                <label for="walkin_user_name">Player Name *</label>
                                <input type="text" id="walkin_user_name" name="user_name" placeholder="John Doe">
                            </div>
                            <div class="form-group">
                                <label for="walkin_partner_name">Partner Name *</label>
                                <input type="text" id="walkin_partner_name" name="partner_name" placeholder="Jane Smith">
                            </div>
                        </div>
                        
                        <!-- Play with Friends Fields (4 players) -->
                        <div id="walkin_friends_fields" style="display:none;border:2px solid #059669;padding:1rem;border-radius:8px;background:#f0fdf4;margin-top:1rem;">
                            <strong style="color:#059669;display:block;margin-bottom:0.5rem;">Register All 4 Players (2 Teams):</strong>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Team 1 - Player 1 *</label>
                                    <input type="text" name="team1_player1" placeholder="John Doe">
                                </div>
                                <div class="form-group">
                                    <label>Team 1 - Player 2 *</label>
                                    <input type="text" name="team1_player2" placeholder="Jane Smith">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Team 2 - Player 1 *</label>
                                    <input type="text" name="team2_player1" placeholder="Mike Johnson">
                                </div>
                                <div class="form-group">
                                    <label>Team 2 - Player 2 *</label>
                                    <input type="text" name="team2_player2" placeholder="Sarah Williams">
                                </div>
                            </div>
                            <p style="font-size:0.85rem;color:#065f46;margin:0.5rem 0 0 0;">
                                💰 Total: PHP 200 for all 4 players (auto-approved & paid)
                            </p>
                        </div>
                        
                        <button type="submit" name="create_walkin" class="btn btn-primary" style="margin-top:1rem;">Register Walk-in</button>
                        <p class="form-hint mt-1">Walk-in registrations are automatically approved and marked as paid.</p>
                    </form>
                </div>
            </div>

            <div class="card mb-2">
                <div class="card-header"><h3>All Sessions</h3></div>
                <div class="card-body table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Title</th><th>Date</th><th>Time</th><th>Players</th><th>Fee/Team</th><th>Status</th><th>FB Post</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $s):
                                $available = (int) $s['max_players'] - (int) $s['current_players'];
                            ?>
                            <tr>
                                <td><?= e($s['title']) ?></td>
                                <td><?= e(formatDate($s['session_date'])) ?></td>
                                <td><?= e(formatTime($s['start_time'])) ?> - <?= e(formatTime($s['end_time'])) ?></td>
                                <td><?= (int) $s['current_players'] ?>/<?= (int) $s['max_players'] ?> (<?= $available ?> open)</td>
                                <td><?= e(formatMoney((float) $s['fee_per_player'] * 2)) ?> (₱<?= number_format($s['fee_per_player'], 0) ?> × 2)</td>
                                <td><span class="badge <?= statusBadgeClass($s['status']) ?>"><?= e($s['status']) ?></span></td>
                                <td>
                                    <textarea class="fb-post-box" style="min-height:100px;font-size:0.8rem;" readonly onclick="this.select()"><?= e(generateFacebookPost($s)) ?></textarea>
                                </td>
                                <td>
                                    <?php if ($s['status'] === 'active'): ?>
                                    <form method="POST" style="display:inline-flex;gap:0.5rem;flex-direction:column;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="session_id" value="<?= (int) $s['id'] ?>">
                                        <button type="submit" name="generate_matches" class="btn btn-sm btn-primary">Generate Matches</button>
                                        <button type="submit" name="finish_session" class="btn btn-sm btn-success" onclick="return confirm('Mark this session as completed? This will close the session.')">Finish Session</button>
                                        <button type="submit" name="cancel_session" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this session?')">Cancel Session</button>
                                    </form>
                                    <?php else: ?>
                                        <span class="badge <?= statusBadgeClass($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="form-hint mt-1"><strong>Note:</strong> Match generation includes all approved registrations (both online and walk-in players).</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Registrations</h3></div>
                <div class="card-body table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th>Session</th><th>Type</th><th>Team Players</th><th>Match Preference</th><th>Contact</th><th>Payment Method</th><th>Total</th><th>Payment Status</th><th>Registration Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                            <tr>
                                <td><?= e($reg['title']) ?></td>
                                <td><span class="badge <?= $reg['booking_type'] === 'walk-in' ? 'badge-info' : 'badge-muted' ?>"><?= e($reg['booking_type']) ?></span></td>
                                <td>
                                    <strong><?= e($reg['display_name']) ?></strong>
                                    &
                                    <strong><?= e($reg['partner_name']) ?></strong>
                                </td>
                                <td>
                                    <?php if (($reg['match_preference'] ?? 'random') === 'random'): ?>
                                        <span class="badge badge-primary">🎲 Random</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">👥 Friends</span>
                                        <?php if (!empty($reg['friend_group'])): ?>
                                            <br><small style="color:#6b7280;"><?= e($reg['friend_group']) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e($reg['display_phone']) ?>
                                    <?php if ($reg['email']): ?>
                                        <br><small><?= e($reg['email']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-muted"><?= e(ucfirst($reg['payment_method'])) ?></span></td>
                                <td><?= e(formatMoney((float) $reg['total_amount'])) ?></td>
                                <td>
                                    <?php if ($reg['status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="registration_id" value="<?= (int) $reg['id'] ?>">
                                            <select name="payment_status" class="btn btn-sm <?= ($reg['payment_status'] ?? 'unpaid') === 'paid' ? 'btn-success' : 'btn-warning' ?>">
                                                <option value="unpaid" <?= ($reg['payment_status'] ?? 'unpaid') === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                                                <option value="paid" <?= ($reg['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                            </select>
                                            <button type="submit" name="update_payment" class="btn btn-sm btn-primary" style="margin-left:4px;">Update</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge <?= statusBadgeClass($reg['payment_status'] ?? 'unpaid') ?>"><?= e($reg['payment_status'] ?? 'unpaid') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= statusBadgeClass($reg['status']) ?>"><?= e($reg['status']) ?></span></td>
                                <td>
                                    <?php if ($reg['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="registration_id" value="<?= (int) $reg['id'] ?>">
                                        <?php if (($reg['payment_status'] ?? '') === 'paid'): ?>
                                            <button type="submit" name="approve_registration" class="btn btn-sm btn-primary">Approve</button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-primary" disabled title="Payment must be marked as paid first">Approve</button>
                                        <?php endif; ?>
                                        <button type="submit" name="reject_registration" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Friend Groups Schedule Section -->
            <div class="card mb-2">
                <div class="card-header">
                    <h3>👥 Friend Groups Schedule</h3>
                    <p style="margin:0.5rem 0 0 0;font-size:0.9rem;color:#6b7280;">Teams who registered to play with their friend groups and their assigned game numbers</p>
                </div>
                <div class="card-body">
                    <?php if (empty($friendGroups)): ?>
                        <p class="text-muted text-center" style="padding: 2rem;">
                            No friend groups registered yet. Friend groups will appear here when teams select "Play with Friends" during registration.
                        </p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Session</th>
                                        <th>Date</th>
                                        <th>Friend Group Name</th>
                                        <th>Teams Count</th>
                                        <th>Teams</th>
                                        <th>Game Numbers</th>
                                        <th>Matches</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($friendGroups as $fg): ?>
                                    <tr>
                                        <td><?= e($fg['session_title']) ?></td>
                                        <td><?= e(formatDate($fg['session_date'])) ?></td>
                                        <td>
                                            <strong style="color:#059669;">👥 <?= e($fg['friend_group']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-info"><?= (int)$fg['team_count'] ?> teams</span>
                                        </td>
                                        <td style="max-width:300px;">
                                            <small style="line-height:1.6;">
                                                <?= e($fg['teams']) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($fg['first_game']): ?>
                                                <span class="badge badge-primary">
                                                    <?php if ($fg['first_game'] == $fg['last_game']): ?>
                                                        Game <?= (int)$fg['first_game'] ?>
                                                    <?php else: ?>
                                                        Game <?= (int)$fg['first_game'] ?> - <?= (int)$fg['last_game'] ?>
                                                    <?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Not matched yet</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($fg['matches_count'] > 0): ?>
                                                <span class="badge badge-success"><?= (int)$fg['matches_count'] ?> matches</span>
                                            <?php else: ?>
                                                <span class="badge badge-muted">0 matches</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="form-hint mt-1">
                            <strong>📌 Note:</strong> Friend groups are matched together. Teams in the same group only play against each other. Game numbers show when they'll play.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($matches) || !empty($rounds)): ?>
            <div class="card mt-2">
                <div class="card-header">
                    <h3>Generated Matches (Doubles)</h3>
                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <form method="GET" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <select name="filter_session" onchange="this.form.submit()" style="padding: 0.4rem 0.75rem; border-radius: 6px; border: 2px solid #e8ece9;">
                                <option value="0">All Sessions</option>
                                <?php foreach ($sessionsWithMatches as $sess): ?>
                                    <option value="<?= (int)$sess['id'] ?>" <?= $filterSession == $sess['id'] ? 'selected' : '' ?>>
                                        <?= e($sess['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="filter_round" onchange="this.form.submit()" style="padding: 0.4rem 0.75rem; border-radius: 6px; border: 2px solid #e8ece9;">
                                <option value="0">All Rounds</option>
                                <?php foreach ($rounds as $round): ?>
                                    <option value="<?= (int)$round ?>" <?= $filterRound == $round ? 'selected' : '' ?>>
                                        Round <?= (int)$round ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($filterRound > 0 || $filterSession > 0): ?>
                                <a href="open-play.php" class="btn btn-sm btn-secondary" style="text-decoration: none;">Clear Filters</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="card-body table-wrap">
                    <?php if (empty($matches)): ?>
                        <p class="text-muted text-center" style="padding: 2rem;">
                            <?php if ($filterRound > 0 || $filterSession > 0): ?>
                                No matches found for the selected filters.
                            <?php else: ?>
                                No matches generated yet. Create a session and generate matches to get started.
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr><th>Session</th><th>Round</th><th>Game #</th><th>Match</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matches as $m): 
                                // Use user_name if available (walk-in or online with custom name), otherwise fullname
                                $p1 = $m['p1_name'] ?: $m['p1_account'] ?: 'Player 1';
                                $p2 = $m['p2_name'] ?: $m['p2_account'] ?: 'Player 2';
                                $p3 = $m['p3_name'] ?: $m['p3_account'] ?: 'Player 3';
                                $p4 = $m['p4_name'] ?: $m['p4_account'] ?: 'Player 4';
                            ?>
                            <!-- Single match row: Team 1 vs Team 2 -->
                            <tr>
                                <td><?= e($m['session_title']) ?></td>
                                <td>Round <?= (int) $m['match_round'] ?></td>
                                <td><span class="badge badge-info">Game <?= (int) $m['game_number'] ?></span></td>
                                <td>
                                    <strong><?= e($p1) ?> & <?= e($m['p1_partner']) ?></strong>
                                    <span style="color: #666;"> vs </span>
                                    <strong><?= e($p3) ?> & <?= e($m['p3_partner']) ?></strong>
                                </td>
                                <td><span class="badge <?= statusBadgeClass($m['match_status']) ?>"><?= e($m['match_status']) ?></span></td>
                                <td>
                                    <?php if ($m['match_status'] !== 'completed'): ?>
                                        <form method="POST" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="match_id" value="<?= (int) $m['id'] ?>">
                                            <button type="submit" name="finish_match" class="btn btn-sm btn-success">Finish</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="match_id" value="<?= (int) $m['id'] ?>">
                                            <button type="submit" name="reset_match" class="btn btn-sm btn-secondary">Reset</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
