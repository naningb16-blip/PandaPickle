<?php
/**
 * Cloudinary Configuration
 * Upload images to Cloudinary cloud storage (persistent across Render deployments)
 */

// Cloudinary credentials - get these from your Cloudinary dashboard
// https://console.cloudinary.com/
define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME') ?: 'deiy19kv4');
define('CLOUDINARY_API_KEY', getenv('CLOUDINARY_API_KEY') ?: '728712179468544');
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET') ?: 'sukP2I-zMeBWqwq2X0VGhQ6csWw');

/**
 * Upload image to Cloudinary using unsigned upload (simpler, no signature needed)
 * 
 * @param string $filePath Path to the file to upload
 * @param string $folder Folder name in Cloudinary (e.g., 'receipts')
 * @return array|false Returns array with 'url' and 'public_id' on success, false on failure
 */
function uploadToCloudinary($filePath, $folder = 'receipts') {
    $cloudName = CLOUDINARY_CLOUD_NAME;
    
    // Check if cloud name is configured
    if ($cloudName === 'your_cloud_name' || empty($cloudName)) {
        error_log('Cloudinary cloud name not configured.');
        return false;
    }
    
    // Use unsigned upload with upload preset - NO SIGNATURE REQUIRED!
    $uploadUrl = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";
    $uploadPreset = 'pandapickle_receipts'; // The preset you just created
    
    $postData = [
        'file' => new CURLFile($filePath),
        'upload_preset' => $uploadPreset
    ];
    
    // Upload via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Cloudinary upload cURL error: {$curlError}");
        return false;
    }
    
    if ($httpCode !== 200) {
        error_log("Cloudinary upload failed with HTTP code {$httpCode}: {$response}");
        
        // Store last error for test page
        $GLOBALS['cloudinary_last_error'] = [
            'http_code' => $httpCode,
            'response' => $response,
            'error_data' => json_decode($response, true)
        ];
        
        return false;
    }
    
    $result = json_decode($response, true);
    
    if (!$result || !isset($result['secure_url'])) {
        error_log("Cloudinary upload failed: Invalid response");
        return false;
    }
    
    return [
        'url' => $result['secure_url'],
        'public_id' => $result['public_id']
    ];
}

/**
 * Delete image from Cloudinary
 * 
 * @param string $publicId The public_id of the image to delete
 * @return bool True on success, false on failure
 */
function deleteFromCloudinary($publicId) {
    $cloudName = CLOUDINARY_CLOUD_NAME;
    $apiKey = CLOUDINARY_API_KEY;
    $apiSecret = CLOUDINARY_API_SECRET;
    
    if (empty($publicId)) {
        return false;
    }
    
    $timestamp = time();
    $signatureString = "public_id={$publicId}&timestamp={$timestamp}{$apiSecret}";
    $signature = sha1($signatureString);
    
    $deleteUrl = "https://api.cloudinary.com/v1_1/{$cloudName}/image/destroy";
    
    $postData = [
        'public_id' => $publicId,
        'api_key' => $apiKey,
        'timestamp' => $timestamp,
        'signature' => $signature
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $deleteUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}
