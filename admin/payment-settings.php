<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = $_POST['settings'] ?? [];
    
    try {
        $db->beginTransaction();
        
        foreach ($updates as $key => $value) {
            $stmt = $db->prepare('UPDATE payment_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?');
            $stmt->execute([trim($value), $key]);
        }
        
        $db->commit();
        flash('success', 'Payment settings updated successfully!');
        header('Location: payment-settings.php');
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        flash('error', 'Error updating settings: ' . $e->getMessage());
    }
}

// Get all payment settings
$settings = $db->query('SELECT * FROM payment_settings ORDER BY display_order')->fetchAll();

$basePath = '../';
$pageTitle = 'Payment Settings - Admin';
$currentPage = 'admin';
$adminPage = 'payment-settings';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Payment Settings</h1>
        <p>Configure payment information displayed to customers</p>
    </div>

    <div class="admin-layout">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>

        <div>
            <div class="card">
                <div class="card-header">
                    <h3>💳 Payment Information</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-2">
                        <strong>ℹ️ About Payment Settings:</strong><br>
                        This information will be shown to customers when they click "Pay Now" on their reservations.
                        Make sure all details are accurate.
                    </div>

                    <form method="POST">
                        <?php foreach ($settings as $setting): ?>
                            <div class="form-group">
                                <label for="<?= e($setting['setting_key']) ?>">
                                    <?= e($setting['setting_label']) ?>
                                </label>
                                <?php if ($setting['setting_key'] === 'payment_instructions'): ?>
                                    <textarea 
                                        id="<?= e($setting['setting_key']) ?>" 
                                        name="settings[<?= e($setting['setting_key']) ?>]" 
                                        rows="4"
                                        class="form-control"
                                    ><?= e($setting['setting_value']) ?></textarea>
                                <?php else: ?>
                                    <input 
                                        type="text" 
                                        id="<?= e($setting['setting_key']) ?>" 
                                        name="settings[<?= e($setting['setting_key']) ?>]" 
                                        value="<?= e($setting['setting_value']) ?>"
                                        class="form-control"
                                    >
                                <?php endif; ?>
                                <small class="form-hint">Key: <?= e($setting['setting_key']) ?></small>
                            </div>
                        <?php endforeach; ?>

                        <div style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-2">
                <div class="card-header">
                    <h3>👀 Preview</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">This is how customers will see your payment information:</p>
                    <div style="border: 2px solid #059669; border-radius: 8px; padding: 1.5rem; background: #f0fdf4;">
                        <h3 style="color: #059669; margin-top: 0;">💳 Payment Information</h3>
                        
                        <?php
                        $settingsData = [];
                        foreach ($settings as $s) {
                            $settingsData[$s['setting_key']] = $s['setting_value'];
                        }
                        ?>
                        
                        <div style="margin-bottom: 1rem;">
                            <strong>GCash:</strong><br>
                            <span style="font-size: 1.1rem; color: #047857;">
                                <?= e($settingsData['gcash_number']) ?>
                            </span><br>
                            <small><?= e($settingsData['gcash_name']) ?></small>
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <strong>Bank Transfer:</strong><br>
                            Bank: <span style="color: #047857;"><?= e($settingsData['bank_name']) ?></span><br>
                            Account #: <span style="color: #047857;"><?= e($settingsData['bank_account_number']) ?></span><br>
                            <small><?= e($settingsData['bank_account_name']) ?></small>
                        </div>

                        <div style="margin-bottom: 1rem; padding: 1rem; background: white; border-radius: 6px;">
                            <strong>📝 Instructions:</strong><br>
                            <?= nl2br(e($settingsData['payment_instructions'])) ?>
                        </div>

                        <div>
                            <strong>📞 Questions?</strong><br>
                            Contact us: <span style="color: #047857;"><?= e($settingsData['contact_number']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
