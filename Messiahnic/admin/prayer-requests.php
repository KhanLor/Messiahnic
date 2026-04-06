<?php
require __DIR__ . '/../bootstrap.php';
require_admin();
ensure_daily_ai_prayers();

$pageTitle = 'Manage Prayer Requests';
$error = null;
$editingRequest = null;

if (is_post()) {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? '';

        $requestId = (int) ($_POST['request_id'] ?? 0);
        if ($requestId <= 0) {
            throw new RuntimeException('Prayer request not found.');
        }

        if ($action === 'approve') {
            $statement = db()->prepare('UPDATE prayer_requests SET status = "approved" WHERE id = ?');
            $statement->execute([$requestId]);
            seed_notification_from_action(
                'Prayer request approved',
                'Prayer',
                'A prayer request was approved and is now visible to the community.',
                'prayer-requests.php'
            );
            flash('success', 'Prayer request approved.');
        } elseif ($action === 'delete') {
            $statement = db()->prepare('DELETE FROM prayer_requests WHERE id = ?');
            $statement->execute([$requestId]);
            flash('success', 'Prayer request deleted.');
        } elseif ($action === 'update') {
            $message = trim($_POST['message'] ?? '');
            $status = trim($_POST['status'] ?? 'pending');

            if ($message === '') {
                throw new RuntimeException('Prayer message cannot be empty.');
            }

            if (!in_array($status, ['pending', 'approved'], true)) {
                throw new RuntimeException('Invalid prayer status.');
            }

            $statement = db()->prepare('UPDATE prayer_requests SET message = ?, status = ? WHERE id = ?');
            $statement->execute([$message, $status, $requestId]);
            flash('success', 'Prayer request updated.');
        } else {
            throw new RuntimeException('Invalid action.');
        }

        redirect('admin/prayer-requests.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$requests = [];
try {
    $statement = db()->query('SELECT pr.*, u.name, u.email FROM prayer_requests pr JOIN users u ON u.id = pr.user_id ORDER BY pr.created_at DESC, pr.id DESC');
    $requests = $statement->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Prayer requests could not be loaded yet.');
}

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $statement = db()->prepare('SELECT pr.*, u.name, u.email FROM prayer_requests pr JOIN users u ON u.id = pr.user_id WHERE pr.id = ? LIMIT 1');
    $statement->execute([$editId]);
    $editingRequest = $statement->fetch() ?: null;

    if (!$editingRequest) {
        flash('error', 'Prayer request not found for editing.');
        redirect('admin/prayer-requests.php');
    }
}

$isEditMode = $editingRequest !== null;
$messageValue = old('message', $editingRequest['message'] ?? '');
$statusValue = old('status', $editingRequest['status'] ?? 'pending');

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="section-title">
        <h2>Prayer messages</h2>
        <span class="muted">Auto-generated each day: morning, afternoon, and evening</span>
    </div>

    <?php if ($isEditMode): ?>
        <button class="btn btn-primary" type="button" data-open-modal="prayerModal">Open edit prayer form</button>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="list-stack">
        <?php foreach ($requests as $request): ?>
            <article class="stack-item">
                <div class="meta">
                    <span><i class="fa-solid fa-user"></i> <?= e($request['name']) ?></span>
                    <span class="badge"><?= e($request['status']) ?></span>
                    <span><?= e(format_date($request['created_at'])) ?></span>
                </div>
                <p><?= e($request['message']) ?></p>
                <div class="actions">
                    <a class="btn btn-primary" href="<?= e(app_url('admin/prayer-requests.php?edit=' . (int) $request['id'])) ?>">Edit</a>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-primary" type="submit">Approve</button>
                    </form>
                    <form method="post" onsubmit="return confirm('Delete this request?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-ghost" type="submit">Delete</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$requests): ?>
            <p class="muted">No prayer requests found.</p>
        <?php endif; ?>
    </div>
</section>

<?php if ($isEditMode): ?>
    <div class="modal modal-open" data-modal="prayerModal" aria-hidden="false">
        <div class="modal-backdrop" data-close-modal></div>
        <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="prayerModalTitle">
            <div class="section-title">
                <h2 id="prayerModalTitle">Edit prayer request</h2>
                <button class="modal-close" type="button" data-close-modal aria-label="Close modal">×</button>
            </div>
            <form class="form" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="request_id" value="<?= (int) $editingRequest['id'] ?>">
                <label>
                    Message
                    <textarea name="message" required><?= e($messageValue) ?></textarea>
                </label>
                <label>
                    Status
                    <select name="status">
                        <option value="pending" <?= $statusValue === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $statusValue === 'approved' ? 'selected' : '' ?>>Approved</option>
                    </select>
                </label>
                <div class="actions">
                    <button class="btn btn-primary" type="submit">Update prayer request</button>
                    <a class="btn btn-ghost" href="<?= e(app_url('admin/prayer-requests.php')) ?>">Cancel edit</a>
                    <button class="btn btn-ghost" type="button" data-close-modal>Close</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
