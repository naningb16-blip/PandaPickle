<?php
// Debug friend groups query

require_once __DIR__ . '/config/db.php';

$db = getDB();

echo "<h2>Debug Friend Groups Schedule</h2>";

// Check registrations with friend groups
echo "<h3>1. Registrations with 'friends' preference:</h3>";
$friendRegs = $db->query("
    SELECT 
        id,
        session_id,
        user_id,
        user_name,
        partner_name,
        match_preference,
        friend_group,
        status
    FROM open_play_registrations
    WHERE match_preference = 'friends'
    ORDER BY friend_group, id
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($friendRegs)) {
    echo "<p style='color:orange;'>⚠️ No registrations with 'friends' preference found.</p>";
    echo "<p>This means no one has selected 'Play with Friends' yet.</p>";
} else {
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Session</th><th>User Name</th><th>Partner</th><th>Friend Group</th><th>Status</th></tr>";
    foreach ($friendRegs as $reg) {
        $highlight = $reg['status'] === 'approved' ? "style='background-color:#90EE90;'" : "";
        echo "<tr $highlight>";
        echo "<td>" . $reg['id'] . "</td>";
        echo "<td>" . $reg['session_id'] . "</td>";
        echo "<td>" . htmlspecialchars($reg['user_name'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($reg['partner_name']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($reg['friend_group'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . $reg['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $approvedCount = count(array_filter($friendRegs, fn($r) => $r['status'] === 'approved'));
    echo "<p><strong>Approved friend registrations:</strong> $approvedCount</p>";
}

// Check matches
echo "<hr><h3>2. Current Matches:</h3>";
$matches = $db->query("
    SELECT 
        m.id,
        m.session_id,
        m.match_round,
        m.game_number,
        m.player1_reg_id,
        m.player2_reg_id,
        m.player3_reg_id,
        m.player4_reg_id,
        r1.friend_group as p1_group,
        r2.friend_group as p2_group
    FROM open_play_matches m
    LEFT JOIN open_play_registrations r1 ON r1.id = m.player1_reg_id
    LEFT JOIN open_play_registrations r2 ON r2.id = m.player2_reg_id
    ORDER BY m.game_number
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($matches)) {
    echo "<p style='color:orange;'>⚠️ No matches generated yet.</p>";
} else {
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Match ID</th><th>Session</th><th>Round</th><th>Game #</th><th>Team 1 Reg ID</th><th>Team 2 Reg ID</th><th>Friend Group</th></tr>";
    foreach ($matches as $match) {
        $group = $match['p1_group'] ?? 'random';
        $highlight = $group !== 'random' && !empty($group) ? "style='background-color:#FFFACD;'" : "";
        echo "<tr $highlight>";
        echo "<td>" . $match['id'] . "</td>";
        echo "<td>" . $match['session_id'] . "</td>";
        echo "<td>" . $match['match_round'] . "</td>";
        echo "<td><strong>Game " . $match['game_number'] . "</strong></td>";
        echo "<td>" . $match['player1_reg_id'] . "</td>";
        echo "<td>" . $match['player3_reg_id'] . "</td>";
        echo "<td>" . htmlspecialchars($group) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Run the exact Friend Groups Schedule query
echo "<hr><h3>3. Friend Groups Schedule Query Result:</h3>";
$friendGroupsQuery = "
    SELECT 
        s.title as session_title,
        s.session_date,
        reg.friend_group,
        reg.match_preference,
        COUNT(DISTINCT reg.id) as team_count,
        GROUP_CONCAT(DISTINCT CONCAT(COALESCE(reg.user_name, u.fullname), ' & ', reg.partner_name) SEPARATOR ' | ') as teams,
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
    GROUP BY s.id, reg.friend_group
    ORDER BY s.session_date DESC, reg.friend_group
";

try {
    $friendGroups = $db->query($friendGroupsQuery)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($friendGroups)) {
        echo "<p style='color:orange;'>⚠️ No friend groups found by the query.</p>";
        echo "<p><strong>Possible reasons:</strong></p>";
        echo "<ul>";
        echo "<li>No registrations with match_preference = 'friends'</li>";
        echo "<li>Friend group names are NULL or empty</li>";
        echo "<li>Registrations are not 'approved' yet</li>";
        echo "</ul>";
    } else {
        echo "<p style='color:green;'>✅ Found " . count($friendGroups) . " friend group(s)!</p>";
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>Session</th><th>Date</th><th>Friend Group</th><th>Teams</th><th>Team Names</th><th>First Game</th><th>Last Game</th><th>Matches</th></tr>";
        foreach ($friendGroups as $fg) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fg['session_title']) . "</td>";
            echo "<td>" . htmlspecialchars($fg['session_date']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($fg['friend_group']) . "</strong></td>";
            echo "<td>" . $fg['team_count'] . "</td>";
            echo "<td>" . htmlspecialchars($fg['teams']) . "</td>";
            echo "<td>" . ($fg['first_game'] ?? '<span style="color:red;">NULL</span>') . "</td>";
            echo "<td>" . ($fg['last_game'] ?? '<span style="color:red;">NULL</span>') . "</td>";
            echo "<td>" . $fg['matches_count'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Query error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><h3>Summary:</h3>";
echo "<ul>";
echo "<li>✅ Database columns exist</li>";
echo "<li>Matches with game numbers: " . count($matches) . "</li>";
echo "<li>Friend registrations: " . count($friendRegs) . "</li>";
echo "<li>Friend groups in schedule: " . (isset($friendGroups) ? count($friendGroups) : 0) . "</li>";
echo "</ul>";
