<?php
require __DIR__ . '/bootstrap.php';
require_login();

$pageTitle = 'Profile';
$user = current_user();
$error = null;

if (is_post()) {
    try {
        verify_csrf();
        $name = trim($_POST['name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $testimony = trim($_POST['testimony'] ?? '');

        if ($name === '') {
            throw new RuntimeException('Name is required.');
        }

        $photoPath = $user['photo'] ?? null;
        if (!empty($_FILES['photo']['name'])) {
            $photoPath = upload_file($_FILES['photo'], 'profiles', ['jpg', 'jpeg', 'png', 'webp']);
        }

        $statement = db()->prepare('UPDATE users SET name = ?, bio = ?, testimony = ?, photo = ? WHERE id = ?');
        $statement->execute([$name, $bio, $testimony, $photoPath, $user['id']]);

        clear_old();
        flash('success', 'Profile updated.');
        redirect('profile.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        set_old();
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="grid cols-2">
    <section class="panel">
        <div class="section-title">
            <h2>My profile</h2>
            <span class="badge"><?= e(ucfirst($user['role'] ?? 'believer')) ?></span>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form class="form" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <label>
                Name
                <input type="text" name="name" value="<?= e(old('name', $user['name'])) ?>" required>
            </label>
            <label>
                Profile photo
                <input type="file" name="photo" accept="image/*">
                <?php if (!empty($user['photo'])): ?>
                    <small class="muted">Current: <?= e($user['photo']) ?></small>
                <?php endif; ?>
            </label>
            <label>
                Bio
                <textarea name="bio"><?= e(old('bio', $user['bio'] ?? '')) ?></textarea>
            </label>
            <label>
                Testimony
                <textarea name="testimony"><?= e(old('testimony', $user['testimony'] ?? '')) ?></textarea>
            </label>
            <button class="btn btn-primary" type="submit">Save changes</button>
        </form>
    </section>

    <aside class="panel">
        <h3>Account details</h3>
        <p><strong>Email:</strong> <?= e($user['email']) ?></p>
        <p><strong>Status:</strong> <?= e($user['status'] ?? 'active') ?></p>
        <p><strong>Joined:</strong> <?= e(format_date($user['created_at'])) ?></p>
        <p class="muted">Admins can manage user roles and activation in the dashboard.</p>
    </aside>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
