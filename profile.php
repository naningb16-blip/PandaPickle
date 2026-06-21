<?php

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = getCurrentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $result = updateProfile($user['id'], $_POST['fullname'] ?? '', $_POST['email'] ?? '', $_POST['phone'] ?? '');
        if ($result['success']) {
            flash('success', $result['message']);
            header('Location: profile.php');
            exit;
        }
        $error = $result['message'];
    }

    if (isset($_POST['change_password'])) {
        $result = changePassword($user['id'], $_POST['current_password'] ?? '', $_POST['new_password'] ?? '');
        if ($result['success']) {
            flash('success', $result['message']);
            header('Location: profile.php');
            exit;
        }
        $error = $result['message'];
    }
}

$user = getCurrentUser();
$pageTitle = 'Profile - PandaPickle';
$currentPage = 'profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Profile Management</h1>
        <p>Update your account information and password.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="grid-2">
        <div class="card">
            <div class="card-header"><h3>Personal Information</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" value="<?= e($user['fullname']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?= e($user['phone']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?= e(ucfirst($user['role'])) ?>" disabled>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Change Password</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
