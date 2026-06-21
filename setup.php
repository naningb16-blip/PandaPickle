<?php

require_once __DIR__ . '/includes/auth.php';

$messages = [];
$errors = [];

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec('DROP DATABASE IF EXISTS pandapickle');

    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    foreach ($statements as $sql) {
        if ($sql !== '') {
            $pdo->exec($sql);
        }
    }

    $messages[] = 'Database recreated successfully.';

    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $demoHash = password_hash('demo123', PASSWORD_DEFAULT);

    $pdo->prepare(
        'INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, ?)'
    )->execute(['System Admin', 'admin@pandapickle.com', '+63 900 000 0001', $adminHash, 'admin']);

    $pdo->prepare(
        'INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, ?)'
    )->execute(['Demo Customer', 'demo@pandapickle.com', '+63 912 345 6789', $demoHash, 'customer']);

    $messages[] = 'Admin: <strong>admin@pandapickle.com</strong> / <strong>admin123</strong>';
    $messages[] = 'Customer: <strong>demo@pandapickle.com</strong> / <strong>demo123</strong>';

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $messages[] = 'Upload directory ready.';

} catch (PDOException $e) {
    $errors[] = 'Setup failed: ' . $e->getMessage();
}

$pageTitle = 'Setup - PandaPickle';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container auth-page">
    <div class="card auth-card">
        <div class="card-header"><h2>System Setup</h2></div>
        <div class="card-body">
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error"><?= e($err) ?></div>
            <?php endforeach; ?>
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-success"><?= $msg ?></div>
            <?php endforeach; ?>
            <?php if (empty($errors)): ?>
                <a href="index.php" class="btn btn-primary btn-block">Go to Home</a>
                <a href="login.php" class="btn btn-secondary btn-block mt-1">Go to Login</a>
            <?php else: ?>
                <a href="setup.php" class="btn btn-secondary btn-block">Retry</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
