<?php
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}
$currentUser = current_user();
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Cormorant+Garamond:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if (!empty($extraHead)): ?>
        <?= $extraHead ?>
    <?php endif; ?>
</head>
<body>
<div class="page-shell">
    <header class="site-header">
        <div class="container nav-wrap">
            <a class="brand" href="<?= e(app_url('index.php')) ?>">
                <span class="brand-mark">M</span>
                <span>
                    <strong><?= e(APP_NAME) ?></strong>
                    <small>Teaching, fellowship, prayer</small>
                </span>
            </a>

            <button class="nav-toggle" type="button" data-nav-toggle aria-label="Toggle navigation">
                <i class="fa-solid fa-bars"></i>
            </button>

            <nav class="site-nav" data-nav>
                <a class="<?= e(nav_is('index.php')) ?>" href="<?= e(app_url('index.php')) ?>">Home</a>
                <a class="<?= e(nav_is('teachings.php')) ?>" href="<?= e(app_url('teachings.php')) ?>">Teachings</a>
                <a class="<?= e(nav_is('scriptures.php')) ?>" href="<?= e(app_url('scriptures.php')) ?>">Scriptures</a>
                <a class="<?= e(nav_is('events.php')) ?>" href="<?= e(app_url('events.php')) ?>">Events</a>
                <a class="<?= e(nav_is('churches.php')) ?>" href="<?= e(app_url('churches.php')) ?>">Churches</a>
                <a class="<?= e(nav_is('community.php')) ?>" href="<?= e(app_url('community.php')) ?>">Live Broadcast</a>
                <a class="<?= e(nav_is('prayer-requests.php')) ?>" href="<?= e(app_url('prayer-requests.php')) ?>">Prayer</a>
                <a class="<?= e(nav_is('notifications.php')) ?>" href="<?= e(app_url('notifications.php')) ?>">Notifications</a>
                <?php if ($currentUser && ($currentUser['role'] ?? 'believer') === 'admin'): ?>
                    <a class="<?= e(nav_is('dashboard.php')) ?>" href="<?= e(app_url('admin/dashboard.php')) ?>">Admin</a>
                <?php endif; ?>
            </nav>

            <div class="nav-actions">
                <button class="theme-toggle" type="button" data-theme-toggle aria-label="Toggle dark mode">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <?php if ($currentUser && ($currentUser['role'] ?? 'believer') === 'admin'): ?>
                    <a class="btn btn-primary" href="<?= e(app_url('auth/logout.php')) ?>">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="site-main">
        <div class="container">
            <?php render_flash_messages(); ?>
