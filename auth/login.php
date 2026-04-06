<?php
require __DIR__ . '/../bootstrap.php';

$pageTitle = 'Login';
$error = null;

if (is_post()) {
    try {
        verify_csrf();
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            throw new RuntimeException('Email and password are required.');
        }

        $statement = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            throw new RuntimeException('Invalid login credentials.');
        }

        if (($user['role'] ?? 'believer') !== 'admin') {
            throw new RuntimeException('Admin access only.');
        }

        if (($user['status'] ?? 'active') !== 'active') {
            throw new RuntimeException('This account is inactive.');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        clear_old();

        flash('success', 'Welcome back, ' . $user['name'] . '.');
        redirect(($user['role'] ?? 'believer') === 'admin' ? 'admin/dashboard.php' : 'index.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        set_old();
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="panel" style="max-width: 560px; margin: 0 auto;">
    <div class="section-title">
        <h2>Admin Login</h2>
        <span class="muted">Administrator access only</span>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="form" method="post">
        <?= csrf_field() ?>
        <label>
            Email
            <input type="email" name="email" value="<?= e(old('email')) ?>" required>
        </label>
        <label>
            Password
            <input type="password" name="password" required>
        </label>
        <button class="btn btn-primary" type="submit">Login</button>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
