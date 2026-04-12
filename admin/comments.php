<?php
require __DIR__ . '/../bootstrap.php';
require_admin();
ensure_comments_table();

$pageTitle = 'Manage Comments';
$error = null;

if (is_post()) {
    try {
        verify_csrf();
        $action = trim($_POST['action'] ?? '');
        $commentId = (int) ($_POST['comment_id'] ?? 0);

        if ($commentId <= 0) {
            throw new RuntimeException('Comment not found.');
        }

        if ($action === 'approve') {
            $statement = db()->prepare('UPDATE comments SET status = "approved" WHERE id = ?');
            $statement->execute([$commentId]);
            flash('success', 'Comment approved.');
        } elseif ($action === 'reject') {
            $statement = db()->prepare('UPDATE comments SET status = "rejected" WHERE id = ?');
            $statement->execute([$commentId]);
            flash('success', 'Comment rejected.');
        } elseif ($action === 'delete') {
            $statement = db()->prepare('DELETE FROM comments WHERE id = ?');
            $statement->execute([$commentId]);
            flash('success', 'Comment deleted.');
        } else {
            throw new RuntimeException('Invalid action.');
        }

        redirect('admin/comments.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$comments = [];
try {
    $statement = db()->query(
        'SELECT c.*, t.title AS teaching_title, u.name AS user_name
         FROM comments c
         JOIN teachings t ON t.id = c.teaching_id
         LEFT JOIN users u ON u.id = c.user_id
         ORDER BY (c.status = "pending") DESC, c.created_at DESC, c.id DESC'
    );
    $comments = $statement->fetchAll();
} catch (Throwable) {
    flash('error', 'Comments could not be loaded yet.');
}

$pendingCount = 0;
foreach ($comments as $comment) {
    if (($comment['status'] ?? '') === 'pending') {
        $pendingCount++;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="section-title">
        <h2>Comment moderation</h2>
        <span class="muted"><?= $pendingCount ?> pending</span>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <p class="muted">Approve comments to show them publicly on teaching pages.</p>

    <div class="list-stack">
        <?php foreach ($comments as $comment): ?>
            <?php $author = (string) ($comment['user_name'] ?? $comment['guest_name'] ?? 'Guest'); ?>
            <article class="stack-item">
                <div class="meta">
                    <span><i class="fa-solid fa-book-open"></i> <?= e((string) $comment['teaching_title']) ?></span>
                    <span><i class="fa-solid fa-user"></i> <?= e($author) ?></span>
                    <span class="badge"><?= e((string) $comment['status']) ?></span>
                    <span><?= e(format_date((string) $comment['created_at'])) ?></span>
                </div>
                <p><?= e((string) $comment['comment']) ?></p>
                <?php if (!empty($comment['guest_email'])): ?>
                    <p class="muted">Email: <?= e((string) $comment['guest_email']) ?></p>
                <?php endif; ?>
                <div class="actions">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-primary" type="submit">Approve</button>
                    </form>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <button class="btn btn-ghost" type="submit">Reject</button>
                    </form>
                    <form method="post" onsubmit="return confirm('Delete this comment?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button class="btn btn-ghost" type="submit">Delete</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$comments): ?>
            <p class="muted">No comments submitted yet.</p>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
