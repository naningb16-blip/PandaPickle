<?php

require_once __DIR__ . '/includes/auth.php';

$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = createPasswordReset($_POST['email'] ?? '');
    if ($result['token']) {
        $resetLink = 'reset-password.php?token=' . $result['token'];
    }
    $message = $result['message'];
}

$pageTitle = 'Forgot Password - PandaPickle';
$currentPage = 'login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container auth-page">
    <div class="card auth-card">
        <div class="card-header"><h2>Forgot Password</h2></div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?= e($message) ?></div>
            <?php endif; ?>

            <?php if ($resetLink): ?>
                <div class="alert alert-info">
                    Reset link (demo — configure email in production):<br>
                    <a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Generate Reset Link</button>
            </form>
            <p class="text-center mt-2"><a href="login.php">Back to Login</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
