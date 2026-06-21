<?php
if (!isset($pageTitle)) {
    $pageTitle = 'PandaPickle';
}
$currentPage = $currentPage ?? '';
$user = isLoggedIn() ? getCurrentUser() : null;
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e($basePath ?? '') ?>assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="<?= e($basePath ?? '') ?>index.php" class="brand">
                <span class="brand-icon">🏓</span>
                <span class="brand-text">PandaPickle</span>
            </a>
            <nav class="main-nav">
                <a href="<?= e($basePath ?? '') ?>index.php" class="<?= $currentPage === 'home' ? 'active' : '' ?>">Home</a>
                <a href="<?= e($basePath ?? '') ?>about.php" class="<?= $currentPage === 'about' ? 'active' : '' ?>">About</a>
                <a href="<?= e($basePath ?? '') ?>contact.php" class="<?= $currentPage === 'contact' ? 'active' : '' ?>">Contact</a>
                <?php if ($user): ?>
                    <?php if (!isAdmin()): ?>
                        <a href="<?= e($basePath ?? '') ?>dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                        <a href="<?= e($basePath ?? '') ?>reservations.php" class="<?= $currentPage === 'reservations' ? 'active' : '' ?>">Reservations</a>
                        <a href="<?= e($basePath ?? '') ?>open-play.php" class="<?= $currentPage === 'open-play' ? 'active' : '' ?>">Open Play</a>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <a href="<?= e($basePath ?? '') ?>admin/index.php" class="<?= str_starts_with($currentPage, 'admin') ? 'active' : '' ?>">Admin</a>
                    <?php endif; ?>
                    <a href="<?= e($basePath ?? '') ?>profile.php" class="<?= $currentPage === 'profile' ? 'active' : '' ?>">Profile</a>
                    <a href="<?= e($basePath ?? '') ?>logout.php" class="btn btn-sm btn-outline">Logout</a>
                <?php else: ?>
                    <a href="<?= e($basePath ?? '') ?>login.php" class="<?= $currentPage === 'login' ? 'active' : '' ?>">Login</a>
                    <a href="<?= e($basePath ?? '') ?>register.php" class="btn btn-sm btn-primary">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="container">
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        </div>
    <?php endif; ?>

    <main class="main-content">
