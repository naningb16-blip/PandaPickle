<?php

// Database configuration - supports both local and production environments
// For InfinityFree: Replace the default values with your InfinityFree credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost'); // Change to: sql123.infinityfree.com (your MySQL host)
define('DB_NAME', getenv('DB_NAME') ?: 'pandapickle'); // Change to: if0_XXXXXXXX_pandapickle (your DB name)
define('DB_USER', getenv('DB_USER') ?: 'root'); // Change to: if0_XXXXXXXX (your username)
define('DB_PASS', getenv('DB_PASSWORD') ?: ''); // Change to: your_database_password

define('COURT_OPEN_HOUR', 5);
define('COURT_CLOSE_HOUR', 22);
define('HOURLY_RATE', 250);
define('OPEN_PLAY_FEE', 50);

define('UPLOAD_DIR', __DIR__ . '/../uploads/payments/');
define('UPLOAD_URL', 'uploads/payments/');

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}
