<?php

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCSRF();
    $result = loginUser($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        flash('success', $result['message']);
        // Redirect admins to admin dashboard, customers to user dashboard
        if (isAdmin()) {
            header('Location: admin/index.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    }
    $error = $result['message'];
}

$pageTitle = 'Login - PandaPickle';
$currentPage = 'login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container auth-page">
    <div class="card auth-card">
        <div class="card-header"><h2>Login to Your Account</h2></div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <?= csrfField() ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Log In</button>
            </form>
            <p class="text-center mt-2">
                <a href="forgot-password.php">Forgot password?</a>
            </p>
            <p class="text-center mt-1 text-muted">
                No account? <a href="register.php" class="text-green">Register here</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
