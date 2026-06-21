<?php

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
        $error = 'Passwords do not match.';
    } else {
    $result = registerUser(
        $_POST['fullname'] ?? '',
        $_POST['email'] ?? '',
        $_POST['phone'] ?? '',
        $_POST['password'] ?? ''
    );

    if ($result['success']) {
        flash('success', $result['message']);
        header('Location: login.php');
        exit;
    }
    $error = $result['message'];
    }
}

$pageTitle = 'Register - PandaPickle';
$currentPage = 'register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container auth-page">
    <div class="card auth-card">
        <div class="card-header"><h2>Create Account</h2></div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" value="<?= e($_POST['fullname'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="+63 912 345 6789">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            <p class="text-center mt-2 text-muted">
                Already have an account? <a href="login.php" class="text-green">Log in</a>
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
