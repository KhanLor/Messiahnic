<?php
require __DIR__ . '/../bootstrap.php';
require_admin();

$pageTitle = 'Manage Users';
$error = null;
$editingUser = null;

if (is_post()) {
    try {
        verify_csrf();
        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'believer';
        $status = $_POST['status'] ?? 'active';

        if ($userId <= 0) {
            throw new RuntimeException('User not found.');
        }

        if (!in_array($role, ['admin', 'believer'], true)) {
            throw new RuntimeException('Invalid role selected.');
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            throw new RuntimeException('Invalid status selected.');
        }

        $statement = db()->prepare('UPDATE users SET role = ?, status = ? WHERE id = ?');
        $statement->execute([$role, $status, $userId]);

        flash('success', 'User updated.');
        redirect('admin/users.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$users = [];
try {
    $users = db()->query('SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC, id DESC')->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Users could not be loaded yet.');
}

$editId = (int) ($_GET['edit'] ?? $_POST['user_id'] ?? 0);
if ($editId > 0) {
    $statement = db()->prepare('SELECT id, name, email, role, status, created_at FROM users WHERE id = ? LIMIT 1');
    $statement->execute([$editId]);
    $editingUser = $statement->fetch() ?: null;
}

$roleValue = old('role', $editingUser['role'] ?? 'believer');
$statusValue = old('status', $editingUser['status'] ?? 'active');

include __DIR__ . '/../includes/header.php';
?>
<section class="section panel">
    <div class="section-title">
        <h2>User management</h2>
        <span class="muted">Assign roles and activate accounts</span>
    </div>

    <?php if ($editingUser): ?>
        <button class="btn btn-primary" type="button" data-open-modal="userModal">Open user edit form</button>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($user['role']) ?></td>
                        <td><?= e($user['status']) ?></td>
                        <td><?= e(format_date($user['created_at'])) ?></td>
                        <td>
                            <a class="btn btn-ghost" href="<?= e(app_url('admin/users.php?edit=' . (int) $user['id'])) ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($editingUser): ?>
    <div class="modal modal-open" data-modal="userModal" aria-hidden="false">
        <div class="modal-backdrop" data-close-modal></div>
        <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
            <div class="section-title">
                <h2 id="userModalTitle">Edit user</h2>
                <button class="modal-close" type="button" data-close-modal aria-label="Close modal">×</button>
            </div>

            <p><strong><?= e($editingUser['name']) ?></strong> <span class="muted">(<?= e($editingUser['email']) ?>)</span></p>

            <form method="post" class="form" style="gap: 0.75rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= (int) $editingUser['id'] ?>">
                <label>
                    Role
                    <select name="role">
                        <option value="believer" <?= $roleValue === 'believer' ? 'selected' : '' ?>>Believer</option>
                        <option value="admin" <?= $roleValue === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value="active" <?= $statusValue === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $statusValue === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </label>
                <div class="actions">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <a class="btn btn-ghost" href="<?= e(app_url('admin/users.php')) ?>">Cancel</a>
                    <button class="btn btn-ghost" type="button" data-close-modal>Close</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
