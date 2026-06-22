<?php
/**
 * Simple Render Status Checker
 * Access: https://pandapickle.onrender.com/test_render_status.php
 * 
 * This checks if PHP is working and shows environment status
 */

// Don't require database or other dependencies
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Render Status - PandaPickle</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
            max-width: 700px;
            width: 100%;
            padding: 2rem;
        }
        h1 {
            color: #059669;
            margin-bottom: 1rem;
        }
        .status-ok {
            color: #059669;
            font-weight: 600;
        }
        .status-error {
            color: #dc2626;
            font-weight: 600;
        }
        .info-box {
            background: #f0fdf4;
            border: 2px solid #059669;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #d1fae5;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #047857;
        }
        .value {
            color: #666;
            text-align: right;
            word-break: break-all;
        }
        code {
            background: #f3f4f6;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Render Status Check</h1>
        <p style="color: #666; margin-bottom: 1.5rem;">PandaPickle deployment status and environment check</p>

        <div class="info-box">
            <h3 style="color: #059669; margin-bottom: 1rem;">🔧 PHP & Environment</h3>
            
            <div class="info-row">
                <span class="label">PHP Version:</span>
                <span class="value status-ok"><?= phpversion() ?></span>
            </div>
            
            <div class="info-row">
                <span class="label">Server Software:</span>
                <span class="value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
            </div>
            
            <div class="info-row">
                <span class="label">Environment:</span>
                <span class="value">
                    <?= getenv('RENDER') ? '<span class="status-ok">Render Production 🚀</span>' : '<span class="status-error">Local Development 💻</span>' ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="label">Document Root:</span>
                <span class="value" style="font-size: 0.8em;"><?= $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown' ?></span>
            </div>
        </div>

        <div class="info-box">
            <h3 style="color: #059669; margin-bottom: 1rem;">📧 Brevo SMTP Config</h3>
            
            <div class="info-row">
                <span class="label">BREVO_SMTP_USER:</span>
                <span class="value">
                    <?php
                    $user = getenv('BREVO_SMTP_USER');
                    echo $user ? '<span class="status-ok">✅ ' . htmlspecialchars($user) . '</span>' : '<span class="status-error">❌ Not set</span>';
                    ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="label">BREVO_SMTP_PASS:</span>
                <span class="value">
                    <?php
                    $pass = getenv('BREVO_SMTP_PASS');
                    echo $pass ? '<span class="status-ok">✅ Set (hidden)</span>' : '<span class="status-error">❌ Not set</span>';
                    ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="label">BREVO_API_KEY:</span>
                <span class="value">
                    <?php
                    $apiKey = getenv('BREVO_API_KEY');
                    echo $apiKey ? '<span class="status-ok">✅ Set (hidden)</span>' : '<span class="status-error">❌ Not set</span>';
                    ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="label">BREVO_FROM_EMAIL:</span>
                <span class="value">
                    <?php
                    $email = getenv('BREVO_FROM_EMAIL');
                    echo $email ? '<span class="status-ok">✅ ' . htmlspecialchars($email) . '</span>' : '<span class="status-error">❌ Not set</span>';
                    ?>
                </span>
            </div>
        </div>

        <div class="info-box">
            <h3 style="color: #059669; margin-bottom: 1rem;">💾 Database Config</h3>
            
            <div class="info-row">
                <span class="label">DB_HOST:</span>
                <span class="value"><?= getenv('DB_HOST') ? '<span class="status-ok">✅ Set</span>' : '<span class="status-error">❌ Not set</span>' ?></span>
            </div>
            
            <div class="info-row">
                <span class="label">DB_NAME:</span>
                <span class="value"><?= getenv('DB_NAME') ? '<span class="status-ok">✅ Set</span>' : '<span class="status-error">❌ Not set</span>' ?></span>
            </div>
            
            <div class="info-row">
                <span class="label">DB_USER:</span>
                <span class="value"><?= getenv('DB_USER') ? '<span class="status-ok">✅ Set</span>' : '<span class="status-error">❌ Not set</span>' ?></span>
            </div>
            
            <div class="info-row">
                <span class="label">DB_PASSWORD:</span>
                <span class="value"><?= getenv('DB_PASSWORD') ? '<span class="status-ok">✅ Set</span>' : '<span class="status-error">❌ Not set</span>' ?></span>
            </div>
        </div>

        <div class="info-box">
            <h3 style="color: #059669; margin-bottom: 1rem;">📂 File Check</h3>
            
            <div class="info-row">
                <span class="label">test_email.php:</span>
                <span class="value">
                    <?php
                    $testEmailExists = file_exists(__DIR__ . '/test_email.php');
                    echo $testEmailExists ? '<span class="status-ok">✅ Exists</span>' : '<span class="status-error">❌ Missing</span>';
                    ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="label">config/db.php:</span>
                <span class="value">
                    <?php
                    $dbConfigExists = file_exists(__DIR__ . '/config/db.php');
                    echo $dbConfigExists ? '<span class="status-ok">✅ Exists</span>' : '<span class="status-error">❌ Missing</span>';
                    ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="label">includes/email.php:</span>
                <span class="value">
                    <?php
                    $emailExists = file_exists(__DIR__ . '/includes/email.php');
                    echo $emailExists ? '<span class="status-ok">✅ Exists</span>' : '<span class="status-error">❌ Missing</span>';
                    ?>
                </span>
            </div>
        </div>

        <?php
        // Try to check database connection
        $dbStatus = 'Not tested';
        $dbError = '';
        
        if (file_exists(__DIR__ . '/config/db.php')) {
            try {
                require_once __DIR__ . '/config/db.php';
                $db = getDB();
                $dbStatus = '<span class="status-ok">✅ Connected</span>';
            } catch (Exception $e) {
                $dbStatus = '<span class="status-error">❌ Connection failed</span>';
                $dbError = htmlspecialchars($e->getMessage());
            }
        }
        ?>

        <div class="info-box">
            <h3 style="color: #059669; margin-bottom: 1rem;">🗄️ Database Connection</h3>
            
            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value"><?= $dbStatus ?></span>
            </div>
            
            <?php if ($dbError): ?>
            <div class="info-row">
                <span class="label">Error:</span>
                <span class="value" style="font-size: 0.8em; color: #dc2626;"><?= $dbError ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div style="margin-top: 2rem; padding: 1rem; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px;">
            <strong style="color: #92400e;">📝 Next Steps:</strong><br><br>
            
            <?php if (!getenv('BREVO_SMTP_USER') || !getenv('BREVO_SMTP_PASS') || !getenv('BREVO_FROM_EMAIL')): ?>
            ⚠️ <strong>Add Brevo environment variables to Render:</strong><br>
            1. Go to Render Dashboard<br>
            2. Add BREVO_SMTP_USER, BREVO_SMTP_PASS, BREVO_FROM_EMAIL<br>
            3. Wait for redeploy<br><br>
            <?php endif; ?>
            
            <?php if ($dbStatus !== '<span class="status-ok">✅ Connected</span>'): ?>
            ⚠️ <strong>Import database schema:</strong><br>
            1. Connect to Render PostgreSQL<br>
            2. Run database/schema.sql<br>
            3. Run all migration files<br><br>
            <?php endif; ?>
            
            <?php if (file_exists(__DIR__ . '/test_email.php')): ?>
            ✅ <strong>Email tester ready:</strong><br>
            <a href="/test_email.php?key=pandapickle2026" style="color: #059669; text-decoration: underline;">
                Click here to test email system
            </a>
            <?php endif; ?>
        </div>

        <div style="margin-top: 1.5rem; text-align: center; color: #666; font-size: 0.9rem;">
            <strong>Deployment Time:</strong> <?= date('F j, Y g:i:s A') ?><br>
            <strong>Timezone:</strong> <?= date_default_timezone_get() ?>
        </div>
    </div>
</body>
</html>
