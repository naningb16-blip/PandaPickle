<?php

require_once __DIR__ . '/../includes/auth.php';

// Only allow existing admins to create new admin accounts
// For the first admin, you can temporarily comment out this line
requireAdmin();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (strlen($fullname) < 2) {
        $error = 'Please enter the admin\'s full name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insert admin user
            $stmt = getDB()->prepare(
                'INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, \'admin\')'
            );
            $stmt->execute([$fullname, $email, $phone, $hash]);
            
            $success = 'Admin account created successfully! Email: ' . e($email);
            
            // Clear form
            $_POST = [];
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Email is already registered.';
            } else {
                $error = 'Error creating admin account: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Register Admin - PandaPickle';
$currentPage = 'users';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="card" style="max-width: 600px; margin: 2rem auto;">
        <div class="card-header">
            <h2>Register New Admin</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="fullname">Full Name *</label>
                    <input 
                        type="text" 
                        id="fullname" 
                        name="fullname" 
                        value="<?= e($_POST['fullname'] ?? '') ?>" 
                        required 
                        autofocus
                        placeholder="Enter admin's full name"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= e($_POST['email'] ?? '') ?>" 
                        required
                        placeholder="admin@example.com"
                    >
                    <small class="form-hint">This will be used to log in</small>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?= e($_POST['phone'] ?? '') ?>"
                        placeholder="09XXXXXXXXX"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        minlength="6"
                        placeholder="Minimum 6 characters"
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                        minlength="6"
                        placeholder="Re-enter password"
                    >
                </div>

                <div class="alert alert-info" style="margin-top: 1.5rem;">
                    <strong>⚠️ Security Note:</strong> Admin accounts have full access to all system features including user management, reservations, and financial data.
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">Create Admin Account</button>
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-hint {
    display: block;
    margin-top: 0.25rem;
    color: #666;
    font-size: 0.875rem;
}

.alert-info {
    background-color: #e7f3ff;
    border-color: #b3d9ff;
    color: #004085;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
