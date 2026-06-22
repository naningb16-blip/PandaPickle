<?php
/**
 * Email Testing Script for PandaPickle
 * Access: https://pandapickle.onrender.com/test_email.php
 * 
 * This script tests the Brevo SMTP email configuration
 * DELETE THIS FILE after testing for security!
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/email.php';

// Simple security: Require a test key in URL
$testKey = $_GET['key'] ?? '';
$correctKey = 'pandapickle2026'; // Change this to something secret!

if ($testKey !== $correctKey) {
    http_response_code(403);
    die('Access denied. Add ?key=pandapickle2026 to the URL to test email.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Tester - PandaPickle</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 2rem;
        }
        h1 {
            color: #059669;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        .subtitle {
            color: #666;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        .config-info {
            background: #f0fdf4;
            border: 2px solid #059669;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .config-info h3 {
            color: #059669;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        .config-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #d1fae5;
        }
        .config-item:last-child {
            border-bottom: none;
        }
        .config-label {
            font-weight: 600;
            color: #047857;
        }
        .config-value {
            color: #666;
            word-break: break-all;
        }
        .status-ok {
            color: #059669;
            font-weight: 600;
        }
        .status-error {
            color: #dc2626;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        input[type="email"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: #059669;
        }
        .btn {
            width: 100%;
            padding: 0.875rem;
            background: #059669;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #047857;
        }
        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        .alert-success {
            background: #d1fae5;
            border: 2px solid #059669;
            color: #065f46;
        }
        .alert-error {
            background: #fee2e2;
            border: 2px solid #dc2626;
            color: #991b1b;
        }
        .alert-warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            color: #92400e;
        }
        .warning-box {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1.5rem;
        }
        .warning-box strong {
            color: #92400e;
        }
        code {
            background: #f3f4f6;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Email Tester</h1>
        <p class="subtitle">Test Brevo SMTP email configuration for PandaPickle</p>

        <?php
        // Display current configuration
        $smtpUser = getenv('BREVO_SMTP_USER') ?: '';
        $smtpPass = getenv('BREVO_SMTP_PASS') ?: '';
        $fromEmail = getenv('BREVO_FROM_EMAIL') ?: '';
        
        $configOk = !empty($smtpUser) && !empty($smtpPass) && !empty($fromEmail);
        ?>

        <div class="config-info">
            <h3>🔧 Current Configuration</h3>
            <div class="config-item">
                <span class="config-label">SMTP User:</span>
                <span class="config-value <?= !empty($smtpUser) ? 'status-ok' : 'status-error' ?>">
                    <?= !empty($smtpUser) ? htmlspecialchars($smtpUser) : '❌ Not set' ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-label">SMTP Pass:</span>
                <span class="config-value <?= !empty($smtpPass) ? 'status-ok' : 'status-error' ?>">
                    <?= !empty($smtpPass) ? '✅ Set (hidden)' : '❌ Not set' ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-label">From Email:</span>
                <span class="config-value <?= !empty($fromEmail) ? 'status-ok' : 'status-error' ?>">
                    <?= !empty($fromEmail) ? htmlspecialchars($fromEmail) : '❌ Not set' ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-label">Status:</span>
                <span class="<?= $configOk ? 'status-ok' : 'status-error' ?>">
                    <?= $configOk ? '✅ Configuration OK' : '❌ Missing credentials' ?>
                </span>
            </div>
        </div>

        <?php
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
            $testEmail = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
            
            if (!$testEmail) {
                echo '<div class="alert alert-error"><strong>❌ Invalid Email:</strong> Please enter a valid email address.</div>';
            } elseif (!$configOk) {
                echo '<div class="alert alert-error"><strong>❌ Configuration Error:</strong> Brevo credentials are not set in environment variables.</div>';
            } else {
                // Send test email
                $subject = "PandaPickle - Test Email from Render";
                $testTime = date('F j, Y \a\t g:i A');
                
                $htmlContent = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                        .content { padding: 20px; background: #f9f9f9; }
                        .info-box { background: #d1fae5; border-left: 4px solid #059669; padding: 15px; margin: 15px 0; border-radius: 4px; }
                        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; background: #f0f0f0; border-radius: 0 0 8px 8px; }
                        h1 { margin: 0; font-size: 24px; }
                        h2 { margin: 0; font-size: 20px; font-weight: normal; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🏓 PandaPickle</h1>
                            <h2>Email Test Successful!</h2>
                        </div>
                        <div class='content'>
                            <p>Congratulations! Your Brevo SMTP email configuration is working correctly.</p>
                            
                            <div class='info-box'>
                                <p><strong>✅ Test Details:</strong></p>
                                <p><strong>Sent From:</strong> " . htmlspecialchars($fromEmail) . "</p>
                                <p><strong>Sent To:</strong> " . htmlspecialchars($testEmail) . "</p>
                                <p><strong>Test Time:</strong> {$testTime}</p>
                                <p><strong>SMTP Server:</strong> smtp-relay.brevo.com (Port 587)</p>
                            </div>
                            
                            <p>This test email confirms that:</p>
                            <ul>
                                <li>✅ Brevo SMTP credentials are configured correctly</li>
                                <li>✅ TLS connection is working</li>
                                <li>✅ Email delivery is functional</li>
                                <li>✅ HTML email rendering is working</li>
                            </ul>
                            
                            <p><strong>Your PandaPickle booking confirmation emails will work just like this!</strong></p>
                        </div>
                        <div class='footer'>
                            <p>This is a test email from PandaPickle email tester.</p>
                            <p>© " . date('Y') . " PandaPickle. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $result = sendBrevoEmail($testEmail, 'Test User', $subject, $htmlContent);
                
                if ($result) {
                    echo '<div class="alert alert-success">
                        <strong>✅ Email Sent Successfully!</strong><br>
                        A test email has been sent to <strong>' . htmlspecialchars($testEmail) . '</strong><br>
                        <br>
                        <strong>Next Steps:</strong><br>
                        1. Check your inbox (and spam folder!)<br>
                        2. Verify the email looks professional<br>
                        3. Confirm all details are correct<br>
                        <br>
                        💡 If you don\'t receive it within 2 minutes, check:<br>
                        - Spam/junk folder<br>
                        - Brevo dashboard for delivery status<br>
                        - Email address spelling
                    </div>';
                } else {
                    echo '<div class="alert alert-error">
                        <strong>❌ Email Failed to Send</strong><br>
                        There was an error sending the test email.<br>
                        <br>
                        <strong>Troubleshooting:</strong><br>
                        - Check Render logs for detailed error messages<br>
                        - Verify SMTP credentials are correct<br>
                        - Check Brevo dashboard for account status<br>
                        - Ensure sender email is verified in Brevo
                    </div>';
                }
            }
        }
        ?>

        <?php if (!$configOk): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Configuration Required</strong><br>
                Please add the following environment variables to Render:
                <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                    <li><code>BREVO_SMTP_USER</code></li>
                    <li><code>BREVO_SMTP_PASS</code></li>
                    <li><code>BREVO_FROM_EMAIL</code></li>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="test_email">📧 Test Email Address:</label>
                <input 
                    type="email" 
                    id="test_email" 
                    name="test_email" 
                    placeholder="Enter your email address"
                    value="<?= htmlspecialchars($_POST['test_email'] ?? '') ?>"
                    required
                    <?= !$configOk ? 'disabled' : '' ?>
                >
                <small style="color: #666; display: block; margin-top: 0.5rem;">
                    We'll send a test email to this address
                </small>
            </div>

            <button 
                type="submit" 
                class="btn"
                <?= !$configOk ? 'disabled' : '' ?>
            >
                🚀 Send Test Email
            </button>
        </form>

        <div class="warning-box">
            <strong>🔒 Security Notice:</strong><br>
            This testing script should be <strong>DELETED</strong> after testing is complete!<br>
            It's only meant for initial email configuration testing.<br>
            <br>
            To delete: Remove <code>test_email.php</code> from your project root.
        </div>

        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #e5e7eb; text-align: center; color: #666; font-size: 0.9rem;">
            <strong>Environment Detected:</strong> 
            <?= getenv('RENDER') ? 'Render Production 🚀' : 'Local Development 💻' ?>
        </div>
    </div>
</body>
</html>
