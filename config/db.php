<?php

// Security Configuration - Disable error display in production
if (getenv('RENDER') || getenv('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    // Development mode - show errors
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Database configuration - supports both local and production environments
// For PostgreSQL on Render
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'pandapickle');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASSWORD') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '5432');

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
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}
