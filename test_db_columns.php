<?php
// Test if database columns exist
require_once __DIR__ . '/config/db.php';

$db = getDB();

echo "Testing database columns...\n\n";

// Check if columns exist
$stmt = $db->query("DESCRIBE exclusive_reservations");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Columns in exclusive_reservations table:\n";
foreach ($columns as $col) {
    echo "  - $col\n";
}

echo "\n";

if (in_array('customer_name', $columns)) {
    echo "✓ customer_name column EXISTS\n";
} else {
    echo "✗ customer_name column MISSING\n";
}

if (in_array('customer_phone', $columns)) {
    echo "✓ customer_phone column EXISTS\n";
} else {
    echo "✗ customer_phone column MISSING\n";
}

echo "\nIf columns are missing, run the migration:\n";
echo "mysql -u root -p pandapickle < database/migration_add_walkin_customer.sql\n";
