<?php
/**
 * Cloudinary Connection Test
 * Run this file to verify your Cloudinary credentials are working
 */

require_once __DIR__ . '/config/cloudinary.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloudinary Test - PandaPickle</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d1fae5; border: 2px solid #059669; color: #065f46; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #fee2e2; border: 2px solid #dc2626; color: #991b1b; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { background: #e0e7ff; border: 2px solid #4f46e5; color: #3730a3; padding: 15px; border-radius: 8px; margin: 10px 0; }
        pre { background: #f3f4f6; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h1 { color: #059669; }
        .btn { background: #059669; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #047857; }
        img { max-width: 100%; border: 2px solid #059669; border-radius: 8px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🌤️ Cloudinary Connection Test</h1>
    
    <div class="info">
        <strong>Current Configuration:</strong><br>
        Cloud Name: <code><?= CLOUDINARY_CLOUD_NAME ?></code><br>
        API Key: <code><?= CLOUDINARY_API_KEY ?></code><br>
        API Secret: <code><?= substr(CLOUDINARY_API_SECRET, 0, 5) ?>***</code> (hidden for security)
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
        echo '<h2>📤 Upload Test Results</h2>';
        
        $file = $_FILES['test_image'];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            echo '<div class="info">File received: ' . htmlspecialchars($file['name']) . ' (' . number_format($file['size'] / 1024, 2) . ' KB)</div>';
            
            // Try to upload to Cloudinary
            $result = uploadToCloudinary($file['tmp_name'], 'pandapickle/test');
            
            if ($result === false) {
                echo '<div class="error">';
                echo '<strong>❌ Upload Failed!</strong><br>';
                echo 'Check your Cloudinary credentials or check error logs.';
                echo '</div>';
            } else {
                echo '<div class="success">';
                echo '<strong>✅ Upload Successful!</strong><br>';
                echo 'Image URL: <a href="' . htmlspecialchars($result['url']) . '" target="_blank">' . htmlspecialchars($result['url']) . '</a><br>';
                echo 'Public ID: <code>' . htmlspecialchars($result['public_id']) . '</code>';
                echo '</div>';
                
                echo '<h3>📸 Uploaded Image Preview:</h3>';
                echo '<img src="' . htmlspecialchars($result['url']) . '" alt="Uploaded test image">';
                
                echo '<div class="info">';
                echo '<strong>Next Steps:</strong><br>';
                echo '1. Go to <a href="https://console.cloudinary.com/console/c-' . CLOUDINARY_CLOUD_NAME . '/media_library" target="_blank">Cloudinary Media Library</a><br>';
                echo '2. Look for folder: pandapickle/test<br>';
                echo '3. Your test image should be there!<br>';
                echo '4. You can delete it from the Media Library if needed.';
                echo '</div>';
            }
        } else {
            echo '<div class="error">File upload error code: ' . $file['error'] . '</div>';
        }
    }
    ?>

    <h2>📁 Upload Test Image</h2>
    <p>Upload a test image to verify Cloudinary connection is working:</p>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="test_image" accept="image/*" required style="margin: 10px 0;">
        <br>
        <button type="submit" class="btn">🚀 Test Upload to Cloudinary</button>
    </form>

    <h2>📋 Troubleshooting</h2>
    <div class="info">
        <strong>If upload fails:</strong><br>
        1. Check that cURL is enabled in PHP (it should be in XAMPP)<br>
        2. Verify your credentials are correct<br>
        3. Check internet connection<br>
        4. Look at PHP error logs for details<br>
        5. Make sure your Cloudinary account is active
    </div>

    <h2>🔗 Useful Links</h2>
    <ul>
        <li><a href="https://console.cloudinary.com/console/c-<?= CLOUDINARY_CLOUD_NAME ?>" target="_blank">Your Cloudinary Dashboard</a></li>
        <li><a href="https://console.cloudinary.com/console/c-<?= CLOUDINARY_CLOUD_NAME ?>/media_library" target="_blank">Media Library</a></li>
        <li><a href="https://cloudinary.com/documentation" target="_blank">Cloudinary Documentation</a></li>
    </ul>

    <p style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e5e7eb; color: #6b7280;">
        <strong>Note:</strong> This is a test file. Delete it after verifying Cloudinary works!<br>
        <code>test_cloudinary.php</code>
    </p>
</body>
</html>
