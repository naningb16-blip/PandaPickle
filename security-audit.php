<?php
/**
 * PandaPickle Security Audit Script
 * 
 * ⚠️ IMPORTANT: DELETE THIS FILE AFTER RUNNING THE AUDIT!
 * This script reveals sensitive information about your system configuration.
 * 
 * Access: http://localhost/PandaPickle/security-audit.php
 */

// Prevent access in production if environment variable is set
if (getenv('ENVIRONMENT') === 'production') {
    http_response_code(404);
    exit('Not Found');
}

$results = [];
$score = 0;
$maxScore = 0;
$db = null; // Initialize $db variable

function checkItem($name, $passed, $message, $severity = 'medium') {
    global $results, $score, $maxScore;
    
    $points = ['critical' => 10, 'high' => 7, 'medium' => 5, 'low' => 3];
    $maxScore += $points[$severity];
    
    if ($passed) {
        $score += $points[$severity];
    }
    
    $results[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
        'severity' => $severity
    ];
}

// Check 1: Database Configuration
try {
    require_once __DIR__ . '/config/db.php';
    $db = getDB();
    checkItem('Database Connection', true, 'Database connection successful', 'critical');
} catch (Exception $e) {
    checkItem('Database Connection', false, 'Database connection failed: ' . $e->getMessage(), 'critical');
}

// Check 2: Password Hashing
$testPassword = 'test123';
$hashedPassword = password_hash($testPassword, PASSWORD_BCRYPT);
$usesStrongHashing = password_verify($testPassword, $hashedPassword);
checkItem('Password Hashing', $usesStrongHashing, 
    $usesStrongHashing ? 'Uses bcrypt password hashing' : 'Weak password hashing detected', 
    'critical');

// Check 3: Session Configuration
session_start();
$sessionSecure = ini_get('session.cookie_httponly') == '1';
checkItem('Session Security', $sessionSecure, 
    $sessionSecure ? 'HTTPOnly cookies enabled' : 'HTTPOnly cookies not enabled - vulnerable to XSS', 
    'high');

// Check 4: File Upload Directory Permissions
$uploadsDir = __DIR__ . '/uploads';
$uploadsWritable = is_writable($uploadsDir);
$uploadsReadable = is_readable($uploadsDir);
checkItem('Upload Directory', $uploadsWritable && $uploadsReadable, 
    $uploadsWritable ? 'Upload directory is writable' : 'Upload directory permissions issue', 
    'medium');

// Check 5: Sensitive Files Exposed
$sensitiveFiles = [
    '.env' => file_exists(__DIR__ . '/.env'),
    'config/db.php' => file_exists(__DIR__ . '/config/db.php'),
    '.git' => is_dir(__DIR__ . '/.git'),
    'composer.json' => file_exists(__DIR__ . '/composer.json')
];
$exposedFiles = array_filter($sensitiveFiles);
checkItem('Sensitive Files', count($exposedFiles) === 0, 
    count($exposedFiles) > 0 ? 'Sensitive files may be accessible: ' . implode(', ', array_keys($exposedFiles)) : 'No sensitive files exposed', 
    'high');

// Check 6: SQL Injection Protection (Check for prepared statements)
$sqlFiles = glob(__DIR__ . '/*.php') + glob(__DIR__ . '/admin/*.php');
$unsafeSqlCount = 0;
foreach ($sqlFiles as $file) {
    $content = file_get_contents($file);
    // Check for unsafe SQL patterns
    if (preg_match('/\$db->query\([\'"].*\$.*[\'"]\)/', $content)) {
        $unsafeSqlCount++;
    }
}
checkItem('SQL Injection Protection', $unsafeSqlCount === 0, 
    $unsafeSqlCount === 0 ? 'Using prepared statements' : "Found $unsafeSqlCount potential SQL injection risks", 
    'critical');

// Check 7: XSS Protection (Check for htmlspecialchars/e() usage)
$hasXSSProtection = function_exists('e');
checkItem('XSS Protection', $hasXSSProtection, 
    $hasXSSProtection ? 'HTML escaping function available' : 'No HTML escaping function found', 
    'high');

// Check 8: HTTPS Configuration
$isHTTPS = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
checkItem('HTTPS', $isHTTPS, 
    $isHTTPS ? 'HTTPS enabled' : 'HTTPS not enabled (acceptable for local development)', 
    'medium');

// Check 9: Error Display
$displayErrors = ini_get('display_errors');
checkItem('Error Display', $displayErrors == '0', 
    $displayErrors == '0' ? 'Error display disabled' : 'Error display enabled - information leakage risk', 
    'medium');

// Check 10: File Upload Validation
$paymentInfoContent = file_exists(__DIR__ . '/payment-info.php') ? file_get_contents(__DIR__ . '/payment-info.php') : '';
$hasFileValidation = strpos($paymentInfoContent, 'allowedTypes') !== false;
checkItem('File Upload Validation', $hasFileValidation, 
    $hasFileValidation ? 'File upload validation present' : 'File upload validation missing', 
    'high');

// Check 11: Admin Authentication
$authContent = file_exists(__DIR__ . '/includes/auth.php') ? file_get_contents(__DIR__ . '/includes/auth.php') : '';
$hasAdminAuth = strpos($authContent, 'requireAdmin') !== false;
checkItem('Admin Authentication', $hasAdminAuth, 
    $hasAdminAuth ? 'Admin authentication function exists' : 'No admin authentication found', 
    'critical');

// Check 12: Database Indexes
if ($db !== null) {
    try {
        $indexQuery = $db->query("SELECT tablename, indexname FROM pg_indexes WHERE schemaname = 'public' LIMIT 5");
        $hasIndexes = $indexQuery->rowCount() > 0;
        checkItem('Database Indexes', $hasIndexes, 
            $hasIndexes ? 'Database indexes configured' : 'No database indexes found - performance issue', 
            'low');
    } catch (Exception $e) {
        checkItem('Database Indexes', false, 'Could not check indexes: ' . $e->getMessage(), 'low');
    }
} else {
    checkItem('Database Indexes', false, 'Cannot check - database connection failed', 'low');
}

// Check 13: CSRF Protection
$hasCSRFProtection = strpos(file_get_contents(__DIR__ . '/includes/functions.php'), 'csrf') !== false;
checkItem('CSRF Protection', $hasCSRFProtection, 
    $hasCSRFProtection ? 'CSRF protection implemented' : 'No CSRF protection found', 
    'high');

// Check 14: Rate Limiting
$hasRateLimit = strpos($authContent, 'rate') !== false || strpos($authContent, 'throttle') !== false;
checkItem('Rate Limiting', $hasRateLimit, 
    $hasRateLimit ? 'Rate limiting implemented' : 'No rate limiting - vulnerable to brute force', 
    'medium');

// Check 15: Cloudinary Configuration
$cloudinaryContent = file_exists(__DIR__ . '/config/cloudinary.php') ? file_get_contents(__DIR__ . '/config/cloudinary.php') : '';
$usesEnvVars = strpos($cloudinaryContent, 'getenv') !== false;
checkItem('Environment Variables', $usesEnvVars, 
    $usesEnvVars ? 'Uses environment variables for secrets' : 'Hardcoded credentials detected', 
    'high');

// Calculate final score
$percentage = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
$grade = $percentage >= 90 ? 'A' : ($percentage >= 80 ? 'B' : ($percentage >= 70 ? 'C' : ($percentage >= 60 ? 'D' : 'F')));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit - PandaPickle</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
        .header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .score-card { background: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .score-number { font-size: 5rem; font-weight: bold; color: <?= $percentage >= 70 ? '#10b981' : ($percentage >= 50 ? '#f59e0b' : '#ef4444') ?>; }
        .grade { font-size: 3rem; font-weight: bold; margin: 20px 0; }
        .results { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .result-item { padding: 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; }
        .result-item:last-child { border-bottom: none; }
        .status-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 20px; }
        .passed { background: #d1fae5; color: #059669; }
        .failed { background: #fee2e2; color: #dc2626; }
        .result-content { flex: 1; }
        .result-name { font-size: 1.1rem; font-weight: 600; margin-bottom: 5px; }
        .result-message { color: #6b7280; font-size: 0.9rem; }
        .severity { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; margin-left: 10px; }
        .severity-critical { background: #fee2e2; color: #991b1b; }
        .severity-high { background: #fed7aa; color: #9a3412; }
        .severity-medium { background: #fef3c7; color: #92400e; }
        .severity-low { background: #dbeafe; color: #1e40af; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin-bottom: 30px; }
        .recommendations { background: #e0e7ff; border-left: 4px solid #4f46e5; padding: 20px; border-radius: 6px; margin-top: 30px; }
        .recommendations h3 { color: #3730a3; margin-bottom: 15px; }
        .recommendations ul { padding-left: 20px; color: #4338ca; }
        .recommendations li { margin-bottom: 8px; }
        .delete-notice { background: #fee2e2; border: 2px solid #dc2626; color: #991b1b; padding: 20px; border-radius: 10px; margin-top: 30px; font-weight: 600; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Security Audit Report</h1>
            <p>PandaPickle Court Management System</p>
            <p style="opacity: 0.9; margin-top: 10px;">Generated: <?= date('F d, Y H:i:s') ?></p>
        </div>

        <div class="warning">
            <strong>⚠️ WARNING:</strong> This audit script reveals sensitive information about your system configuration. 
            <strong>DELETE THIS FILE (security-audit.php) IMMEDIATELY AFTER REVIEWING THE RESULTS!</strong>
        </div>

        <div class="score-card">
            <div class="score-number"><?= $percentage ?>%</div>
            <div class="grade">Grade: <?= $grade ?></div>
            <p style="color: #6b7280; font-size: 1.1rem;">Security Score: <?= $score ?> / <?= $maxScore ?> points</p>
        </div>

        <div class="results">
            <h2 style="margin-bottom: 20px; color: #1f2937;">Detailed Results (<?= count($results) ?> checks)</h2>
            
            <?php foreach ($results as $result): ?>
            <div class="result-item">
                <div class="status-icon <?= $result['passed'] ? 'passed' : 'failed' ?>">
                    <?= $result['passed'] ? '✓' : '✗' ?>
                </div>
                <div class="result-content">
                    <div class="result-name">
                        <?= htmlspecialchars($result['name']) ?>
                        <span class="severity severity-<?= $result['severity'] ?>"><?= $result['severity'] ?></span>
                    </div>
                    <div class="result-message"><?= htmlspecialchars($result['message']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($percentage < 80): ?>
        <div class="recommendations">
            <h3>🔧 Recommended Security Improvements:</h3>
            <ul>
                <?php if (!$sessionSecure): ?>
                <li>Enable HTTPOnly cookies in php.ini: <code>session.cookie_httponly = 1</code></li>
                <?php endif; ?>
                
                <?php if (!$isHTTPS && getenv('ENVIRONMENT') === 'production'): ?>
                <li>Enable HTTPS for production environment</li>
                <?php endif; ?>
                
                <?php if ($displayErrors != '0'): ?>
                <li>Disable error display in production: <code>display_errors = Off</code></li>
                <?php endif; ?>
                
                <?php if (!$hasCSRFProtection): ?>
                <li>Implement CSRF token protection for forms</li>
                <?php endif; ?>
                
                <?php if (!$hasRateLimit): ?>
                <li>Add rate limiting to prevent brute force attacks</li>
                <?php endif; ?>
                
                <?php if (count($exposedFiles) > 0): ?>
                <li>Protect sensitive files with .htaccess or remove from web root</li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="delete-notice">
            🚨 DELETE THIS FILE NOW: security-audit.php 🚨<br>
            This file exposes security information and should not remain on your server!
        </div>
    </div>
</body>
</html>
