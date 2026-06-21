<?php
// Check if game_number column exists in open_play_matches table

require_once __DIR__ . '/config/db.php';

$db = getDB();

echo "<h2>Checking Database Schema</h2>";

// Check open_play_matches columns
echo "<h3>open_play_matches table columns:</h3>";
$stmt = $db->query("SHOW COLUMNS FROM open_play_matches");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse:collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns as $col) {
    $highlight = $col['Field'] === 'game_number' ? "style='background-color:#90EE90;'" : "";
    echo "<tr $highlight>";
    echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check if game_number column exists
$gameNumberExists = false;
foreach ($columns as $col) {
    if ($col['Field'] === 'game_number') {
        $gameNumberExists = true;
        break;
    }
}

if ($gameNumberExists) {
    echo "<p style='color:green;font-weight:bold;'>✅ game_number column EXISTS!</p>";
    
    // Check existing matches
    echo "<h3>Existing matches with game_number:</h3>";
    $matches = $db->query("SELECT id, session_id, match_round, game_number FROM open_play_matches ORDER BY id DESC LIMIT 10")->fetchAll();
    
    if (empty($matches)) {
        echo "<p>No matches found in database.</p>";
    } else {
        echo "<table border='1' style='border-collapse:collapse;'>";
        echo "<tr><th>ID</th><th>Session ID</th><th>Round</th><th>Game Number</th></tr>";
        foreach ($matches as $match) {
            echo "<tr>";
            echo "<td>" . $match['id'] . "</td>";
            echo "<td>" . $match['session_id'] . "</td>";
            echo "<td>" . $match['match_round'] . "</td>";
            echo "<td>" . ($match['game_number'] ?? '<span style="color:red;">NULL</span>') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        $nullCount = $db->query("SELECT COUNT(*) FROM open_play_matches WHERE game_number IS NULL")->fetchColumn();
        if ($nullCount > 0) {
            echo "<p style='color:orange;font-weight:bold;'>⚠️ Warning: $nullCount matches have NULL game_number</p>";
            echo "<p>These matches were created before the migration. You need to:</p>";
            echo "<ol>";
            echo "<li>Delete old matches, OR</li>";
            echo "<li>Generate new matches (which will have game numbers)</li>";
            echo "</ol>";
        } else {
            echo "<p style='color:green;'>✅ All matches have game numbers!</p>";
        }
    }
    
} else {
    echo "<p style='color:red;font-weight:bold;'>❌ game_number column DOES NOT EXIST!</p>";
    echo "<p>You need to run the migration:</p>";
    echo "<pre>mysql -u root -p pandapickle < database/migration_add_match_preference.sql</pre>";
    echo "<p>Or execute the SQL manually in phpMyAdmin.</p>";
}

// Check open_play_registrations columns
echo "<hr>";
echo "<h3>open_play_registrations table columns (checking for match_preference and friend_group):</h3>";
$stmt = $db->query("SHOW COLUMNS FROM open_play_registrations");
$regColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hasMatchPref = false;
$hasFriendGroup = false;

foreach ($regColumns as $col) {
    if ($col['Field'] === 'match_preference') $hasMatchPref = true;
    if ($col['Field'] === 'friend_group') $hasFriendGroup = true;
}

echo "<p>";
echo $hasMatchPref ? "<span style='color:green;'>✅ match_preference exists</span><br>" : "<span style='color:red;'>❌ match_preference missing</span><br>";
echo $hasFriendGroup ? "<span style='color:green;'>✅ friend_group exists</span>" : "<span style='color:red;'>❌ friend_group missing</span>";
echo "</p>";

if (!$hasMatchPref || !$hasFriendGroup) {
    echo "<p style='color:red;font-weight:bold;'>You need to run the migration!</p>";
}
