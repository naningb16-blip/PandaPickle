<?php
/**
 * First-Time Admin Setup Script
 * 
 * This script creates the first admin account for your PandaPickle installation.
 * After creating the first admin, you should DELETE this file for security.
 * 
 * Usage:
 * 1. Access this file in your browser: http://yoursite.com/setup-admin.php
 * 2. Fill in the admin details
 * 3. After successful creation, DELETE this file immediately
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Security: Check if admin already exists
try {
    $stmt = getDB()->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['admin_count'] > 0) {
        die('<h1>Setup Already Complete</h1><p>Admin account already exists. Please delete this file (setup-admin.php) for security.</p>');
    }
} catch (PDOException $e) {
    die('<h1>Database Error</h1><p>Cannot connect to database. Please check your configuration.</p><p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>');
}

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
        $error = 'Please enter your full name.';
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
            // Insert first admin user
            $stmt = getDB()->prepare(
                'INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, \'admin\')'
            );
            $stmt->execute([$fullname, $email, $phone, $hash]);
            
            $success = true;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Email is already registered.';
            } else {
                $error = 'Error creating admin account: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - PandaPickle</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .setup-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 2rem;
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .setup-header h1 {
            color: #333;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .setup-header p {
            color: #666;
            font-size: 0.95rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-hint {
            display: block;
            margin-top: 0.25rem;
            color: #666;
            font-size: 0.875rem;
        }
        
        .btn {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .success-message {
            text-align: center;
        }
        
        .success-message h2 {
            color: #155724;
            margin-bottom: 1rem;
        }
        
        .success-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .success-links a {
            flex: 1;
            padding: 0.75rem;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .success-links a:hover {
            transform: translateY(-2px);
        }
        
        .link-primary {
            background: #667eea;
            color: white;
        }
        
        .link-secondary {
            background: #e9ecef;
            color: #333;
        }
        
        .security-warning {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 6px;
            font-size: 0.875rem;
            color: #856404;
        }
        
        .security-warning strong {
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <?php if ($success): ?>
            <div class="success-message">
                <h2>✓ Admin Account Created!</h2>
                <div class="alert alert-success">
                    Your admin account has been created successfully.
                </div>
                <div class="alert alert-warning">
                    <strong>⚠️ IMPORTANT SECURITY STEP:</strong><br>
                    Please delete the file <code>setup-admin.php</code> from your server immediately to prevent unauthorized access.
                </div>
                <div class="success-links">
                    <a href="login.php" class="link-primary">Go to Login</a>
                    <a href="admin/index.php" class="link-secondary">Admin Panel</a>
                </div>
            </div>
        <?php else: ?>
            <div class="setup-header">
                <h1>🎾 PandaPickle Setup</h1>
                <p>Create your first admin account</p>
            </div>

            <div class="alert alert-warning">
                <strong>⚠️ First-Time Setup</strong><br>
                This page will be disabled after creating the first admin account.
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="fullname">Full Name *</label>
                    <input 
                        type="text" 
                        id="fullname" 
                        name="fullname" 
                        value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" 
                        required 
                        autofocus
                        placeholder="Enter your full name"
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                        required
                        placeholder="admin@example.com"
                    >
                    <small class="form-hint">This will be your login username</small>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
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

                <button type="submit" class="btn">Create Admin Account</button>

                <div class="security-warning">
                    <strong>🔒 Security Note:</strong>
                    After creating your admin account, you MUST delete this file (setup-admin.php) from your server to prevent unauthorized access.
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
