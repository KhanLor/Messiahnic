<?php
require __DIR__ . '/../bootstrap.php';
require_admin();

$pageTitle = 'Admin Dashboard';
$stats = [
    'users' => 0,
    'teachings' => 0,
    'events' => 0,
    'pending_prayers' => 0,
];
$logs = [];

try {
    $stats['users'] = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['teachings'] = (int) db()->query('SELECT COUNT(*) FROM teachings')->fetchColumn();
    $stats['events'] = (int) db()->query('SELECT COUNT(*) FROM events')->fetchColumn();
    $stats['pending_prayers'] = (int) db()->query('SELECT COUNT(*) FROM prayer_requests WHERE status = "pending"')->fetchColumn();
    $logs = latest_notifications(8);
} catch (Throwable $exception) {
    flash('error', 'Dashboard stats are not available yet.');
}

include __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="section-title">
        <h2>Admin dashboard</h2>
        <span class="badge">Role: admin</span>
    </div>
    <div class="kpi-row">
        <div class="kpi"><strong><?= number_format($stats['users']) ?></strong><span class="muted">Total believers</span></div>
        <div class="kpi"><strong><?= number_format($stats['teachings']) ?></strong><span class="muted">Teachings</span></div>
        <div class="kpi"><strong><?= number_format($stats['events']) ?></strong><span class="muted">Events</span></div>
        <div class="kpi"><strong><?= number_format($stats['pending_prayers']) ?></strong><span class="muted">Pending prayers</span></div>
    </div>
</section>

<section class="section grid cols-2">
    <div class="panel">
        <div class="section-title">
            <h2>Manage modules</h2>
            <span class="muted">Quick access</span>
        </div>
        <div class="grid cols-2">
            <a class="card" href="<?= e(app_url('admin/users.php')) ?>"><strong>Users</strong><p class="muted">Activate, deactivate, and assign roles.</p></a>
            <a class="card" href="<?= e(app_url('admin/teachings.php')) ?>"><strong>Teachings</strong><p class="muted">Create teachings and upload media.</p></a>
            <a class="card" href="<?= e(app_url('admin/scriptures.php')) ?>"><strong>Scriptures</strong><p class="muted">Add and highlight verses.</p></a>
            <a class="card" href="<?= e(app_url('admin/events.php')) ?>"><strong>Events</strong><p class="muted">Manage Sabbath and feast day posts.</p></a>
            <a class="card" href="<?= e(app_url('admin/churches.php')) ?>"><strong>Churches</strong><p class="muted">Add and manage church map locations.</p></a>
            <a class="card" href="<?= e(app_url('admin/prayer-requests.php')) ?>"><strong>Prayer requests</strong><p class="muted">Approve or delete requests.</p></a>
            <a class="card" href="<?= e(app_url('admin/community.php')) ?>"><strong>Live broadcast</strong><p class="muted">Set the Facebook Live URL shown on the site.</p></a>
            <a class="card" href="<?= e(app_url('admin/notifications.php')) ?>"><strong>Notifications</strong><p class="muted">Edit and publish site notifications.</p></a>
            <a class="card" href="<?= e(app_url('admin/about.php')) ?>"><strong>About</strong><p class="muted">Manage congregation info, leadership, and faith summary.</p></a>
            <a class="card" href="<?= e(app_url('admin/leadership.php')) ?>"><strong>Leadership</strong><p class="muted">Manage the leader's name, address, contact number, and Facebook page.</p></a>
        </div>
    </div>

    <div class="panel">
        <div class="section-title">
            <h2>Activity log</h2>
            <span class="muted">Latest notifications</span>
        </div>
        <div class="list-stack">
            <?php foreach ($logs as $log): ?>
                <div class="stack-item">
                    <div class="meta">
                        <span class="badge"><?= e($log['type']) ?></span>
                        <span><?= e(format_date($log['created_at'])) ?></span>
                    </div>
                    <strong><?= e($log['title']) ?></strong>
                    <p><?= e($log['message']) ?></p>
                </div>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <p class="muted">No activity yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
