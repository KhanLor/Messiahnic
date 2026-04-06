<?php
require __DIR__ . '/../bootstrap.php';
require_admin();

$pageTitle = 'Manage Notifications';
$error = null;
$editingNotification = null;
$perPage = 5;
$page = max(1, (int) ($_GET['page'] ?? $_POST['page'] ?? 1));
$totalNotifications = 0;
$totalPages = 1;

if (is_post()) {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? 'create';

        if ($action === 'delete') {
            $notificationId = (int) ($_POST['notification_id'] ?? 0);
            $statement = db()->prepare('DELETE FROM notifications WHERE id = ?');
            $statement->execute([$notificationId]);
            flash('success', 'Notification deleted.');
            redirect('admin/notifications.php?page=' . $page);
        }

        $type = trim($_POST['type'] ?? 'General');
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $link = trim($_POST['link'] ?? '');

        if ($title === '' || $message === '') {
            throw new RuntimeException('Title and message are required.');
        }

        if ($action === 'update') {
            $notificationId = (int) ($_POST['notification_id'] ?? 0);
            if ($notificationId <= 0) {
                throw new RuntimeException('Notification not found for editing.');
            }

            $statement = db()->prepare('UPDATE notifications SET type = ?, title = ?, message = ?, link = ? WHERE id = ?');
            $statement->execute([$type, $title, $message, $link ?: null, $notificationId]);
            flash('success', 'Notification updated.');
        } else {
            $statement = db()->prepare('INSERT INTO notifications (type, title, message, link, created_at) VALUES (?, ?, ?, ?, NOW())');
            $statement->execute([$type, $title, $message, $link ?: null]);
            flash('success', 'Notification created.');
        }

        clear_old();
        redirect('admin/notifications.php?page=' . $page);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        set_old();
    }
}

$notifications = [];
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

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $statement = db()->prepare('SELECT * FROM notifications WHERE id = ? LIMIT 1');
    $statement->execute([$editId]);
    $editingNotification = $statement->fetch() ?: null;

    if (!$editingNotification) {
        flash('error', 'Notification not found for editing.');
        redirect('admin/notifications.php?page=' . $page);
    }
}

$isEditMode = $editingNotification !== null;
$formAction = $isEditMode ? 'update' : 'create';
$submitLabel = $isEditMode ? 'Update notification' : 'Create notification';
$typeValue = old('type', $editingNotification['type'] ?? 'General');
$titleValue = old('title', $editingNotification['title'] ?? '');
$messageValue = old('message', $editingNotification['message'] ?? '');
$linkValue = old('link', $editingNotification['link'] ?? '');

include __DIR__ . '/../includes/header.php';
?>
<section class="grid cols-2">
    <section class="panel">
        <div class="section-title">
            <h2><?= $isEditMode ? 'Edit notification' : 'Create notification' ?></h2>
            <?php if ($isEditMode): ?>
                <a class="btn btn-ghost" href="<?= e(app_url('admin/notifications.php?page=' . $page)) ?>">Cancel edit</a>
            <?php else: ?>
                <span class="badge">Admin only</span>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <button class="btn btn-primary" type="button" data-open-modal="notificationModal"><?= $isEditMode ? 'Open edit form' : 'Open notification form' ?></button>
    </section>

    <aside class="panel">
        <div class="section-title">
            <h2>Existing notifications</h2>
            <span class="muted"><?= number_format($totalNotifications) ?> items</span>
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
                        <a class="btn btn-primary" href="<?= e(app_url('admin/notifications.php?edit=' . (int) $notification['id'] . '&page=' . $page)) ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this notification?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="page" value="<?= (int) $page ?>">
                            <input type="hidden" name="notification_id" value="<?= (int) $notification['id'] ?>">
                            <button class="btn btn-ghost" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$notifications): ?>
                <p class="muted">No notifications found.</p>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Admin notifications pages">
                <?php if ($page > 1): ?>
                    <a class="page-link" href="<?= e(app_url('admin/notifications.php?page=' . ($page - 1))) ?>">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a class="page-link <?= $i === $page ? 'is-active' : '' ?>" href="<?= e(app_url('admin/notifications.php?page=' . $i)) ?>"><?= (int) $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a class="page-link" href="<?= e(app_url('admin/notifications.php?page=' . ($page + 1))) ?>">Next</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </aside>
</section>

<div class="modal <?= ($error || $isEditMode) ? 'modal-open' : '' ?>" data-modal="notificationModal" aria-hidden="<?= ($error || $isEditMode) ? 'false' : 'true' ?>">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="notificationModalTitle">
        <div class="section-title">
            <h2 id="notificationModalTitle"><?= $isEditMode ? 'Edit notification' : 'Create notification' ?></h2>
            <button class="modal-close" type="button" data-close-modal aria-label="Close modal">×</button>
        </div>

        <form class="form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= e($formAction) ?>">
            <input type="hidden" name="page" value="<?= (int) $page ?>">
            <?php if ($isEditMode): ?>
                <input type="hidden" name="notification_id" value="<?= (int) $editingNotification['id'] ?>">
            <?php endif; ?>
            <label>
                Type
                <input type="text" name="type" value="<?= e($typeValue) ?>" placeholder="Event, Teaching, Prayer, General">
            </label>
            <label>
                Title
                <input type="text" name="title" value="<?= e($titleValue) ?>" required>
            </label>
            <label>
                Message
                <textarea name="message" required><?= e($messageValue) ?></textarea>
            </label>
            <label>
                Link (optional)
                <input type="text" name="link" value="<?= e($linkValue) ?>" placeholder="events.php or teaching.php?id=1">
            </label>
            <div class="actions">
                <button class="btn btn-primary" type="submit"><?= e($submitLabel) ?></button>
                <?php if ($isEditMode): ?>
                    <a class="btn btn-ghost" href="<?= e(app_url('admin/notifications.php?page=' . $page)) ?>">Cancel edit</a>
                <?php endif; ?>
                <button class="btn btn-ghost" type="button" data-close-modal>Close</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
