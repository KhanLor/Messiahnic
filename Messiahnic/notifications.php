<?php
require __DIR__ . '/bootstrap.php';

$pageTitle = 'Notifications';
$notifications = [];
$currentUser = current_user();
$perPage = 5;
$page = max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1));
$totalNotifications = 0;
$totalPages = 1;

if (is_post()) {
    try {
        verify_csrf();

        if (!$currentUser || ($currentUser['role'] ?? 'believer') !== 'admin') {
            throw new RuntimeException('Only admin can delete notifications.');
        }

        $action = $_POST['action'] ?? '';
        if ($action !== 'delete') {
            throw new RuntimeException('Invalid action.');
        }

        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            throw new RuntimeException('Notification not found.');
        }

        $statement = db()->prepare('DELETE FROM notifications WHERE id = ?');
        $statement->execute([$notificationId]);
        flash('success', 'Notification deleted.');
        redirect('notifications.php?page=' . $page);
    } catch (Throwable $exception) {
        flash('error', $exception->getMessage());
    }
}

try {
    $totalNotifications = (int) db()->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
    $totalPages = max(1, (int) ceil($totalNotifications / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;
    $statement = db()->prepare('SELECT * FROM notifications ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?');
    $statement->bindValue(1, $perPage, PDO::PARAM_INT);
    $statement->bindValue(2, $offset, PDO::PARAM_INT);
    $statement->execute();
    $notifications = $statement->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Notifications could not be loaded yet.');
}

include __DIR__ . '/includes/header.php';
?>
<section class="section panel">
    <div class="section-title">
        <h2>Notifications and updates</h2>
        <span class="muted">New teachings, events, and activity updates</span>
    </div>
    <div class="list-stack">
        <?php foreach ($notifications as $notification): ?>
            <article class="stack-item notification-item">
                <div class="meta">
                    <span class="badge"><?= e($notification['type']) ?></span>
                    <span><?= e(format_date($notification['created_at'])) ?></span>
                </div>
                <h3><?= e($notification['title']) ?></h3>
                <p><?= e($notification['message']) ?></p>
                <div class="actions">
                    <?php if (!empty($notification['link'])): ?>
                        <a class="btn btn-ghost" href="<?= e(app_url($notification['link'])) ?>">Open</a>
                    <?php endif; ?>
                    <?php if ($currentUser && ($currentUser['role'] ?? 'believer') === 'admin'): ?>
                        <form method="post" onsubmit="return confirm('Delete this notification?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="page" value="<?= (int) $page ?>">
                            <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
                            <button class="btn btn-ghost" type="submit">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$notifications): ?>
            <p class="muted">Notifications will appear here when content is added.</p>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Notifications pages">
            <?php if ($page > 1): ?>
                <a class="page-link" href="<?= e(app_url('notifications.php?page=' . ($page - 1))) ?>">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="page-link <?= $i === $page ? 'is-active' : '' ?>" href="<?= e(app_url('notifications.php?page=' . $i)) ?>"><?= (int) $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a class="page-link" href="<?= e(app_url('notifications.php?page=' . ($page + 1))) ?>">Next</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
