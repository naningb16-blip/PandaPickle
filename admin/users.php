<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_role'])) {
        $userId = (int) $_POST['user_id'];
        $role = $_POST['role'] === 'admin' ? 'admin' : 'customer';
        $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $userId]);
        flash('success', 'User role updated.');
    }
    if (isset($_POST['delete_user'])) {
        $userId = (int) $_POST['user_id'];
        if ($userId !== getCurrentUser()['id']) {
            $db->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            flash('success', 'User deleted.');
        }
    }
    header('Location: users.php');
    exit;
}

$users = $db->query('SELECT id, fullname, email, phone, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();

$basePath = '../';
$pageTitle = 'Manage Users - Admin';
$currentPage = 'admin';
$adminPage = 'users';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header"><h1>Manage Users</h1></div>
    <div class="admin-layout">
        <?php require __DIR__ . '/includes/sidebar.php'; ?>
        <div class="card">
            <div class="card-body table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= e($u['fullname']) ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= e($u['phone']) ?></td>
                            <td><span class="badge <?= $u['role'] === 'admin' ? 'badge-success' : 'badge-muted' ?>"><?= e($u['role']) ?></span></td>
                            <td><?= e(formatDate($u['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display:inline-flex;gap:0.5rem;">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <select name="role">
                                        <option value="customer" <?= $u['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="update_role" class="btn btn-sm btn-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
