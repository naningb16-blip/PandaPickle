<?php

require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
$error = '';

if ($token === '') {
    header('Location: forgot-password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
        $error = 'Passwords do not match.';
    } else {
        $result = resetPasswordWithToken($token, $_POST['password'] ?? '');
        if ($result['success']) {
            flash('success', $result['message']);
            header('Location: login.php');
            exit;
        }
        $error = $result['message'];
    }
}

$pageTitle = 'Reset Password - PandaPickle';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container auth-page">
    <div class="card auth-card">
        <div class="card-header"><h2>Reset Password</h2></div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
