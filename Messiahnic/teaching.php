<?php
require __DIR__ . '/bootstrap.php';

$teachingId = (int) ($_GET['id'] ?? 0);
$pageTitle = 'Teaching';
$error = null;

if ($teachingId <= 0) {
    flash('error', 'Teaching not found.');
    redirect('teachings.php');
}

try {
    $statement = db()->prepare('SELECT * FROM teachings WHERE id = ? LIMIT 1');
    $statement->execute([$teachingId]);
    $teaching = $statement->fetch();

    if (!$teaching) {
        flash('error', 'Teaching not found.');
        redirect('teachings.php');
    }

    $comments = db()->prepare('SELECT c.*, u.name, u.photo FROM comments c JOIN users u ON u.id = c.user_id WHERE c.teaching_id = ? ORDER BY c.created_at DESC, c.id DESC');
    $comments->execute([$teachingId]);
    $comments = $comments->fetchAll();
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

include __DIR__ . '/includes/header.php';
?>
<section class="grid cols-2">
    <article class="panel">
        <?php if (!empty($teaching['media_path'])): ?>
            <div class="badge"><i class="fa-solid fa-photo-film"></i> Media attached</div>
            <p><a href="<?= e(app_url($teaching['media_path'])) ?>" target="_blank" rel="noreferrer">Open uploaded file</a></p>
        <?php endif; ?>
        <div class="meta">
            <span class="badge"><?= e($teaching['category']) ?></span>
            <span><?= e($teaching['scripture_reference']) ?></span>
            <span><?= e(format_date($teaching['created_at'])) ?></span>
        </div>
        <h2 style="font-family: 'Cormorant Garamond', Georgia, serif; margin-top: 0;"><?= e($teaching['title']) ?></h2>
        <div><?= nl2br(e($teaching['content'])) ?></div>
    </article>

    <aside class="panel">
        <h3>Comment on this teaching</h3>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if (auth_check()): ?>
            <form class="form" method="post">
                <?= csrf_field() ?>
                <label>
                    Your comment
                    <textarea name="comment" required><?= e(old('comment')) ?></textarea>
                </label>
                <button class="btn btn-primary" type="submit">Post comment</button>
            </form>
        <?php else: ?>
            <p class="muted">Please log in to comment.</p>
            <a class="btn btn-primary" href="<?= e(app_url('auth/login.php')) ?>">Login</a>
        <?php endif; ?>
    </aside>
</section>

<section class="section panel">
    <div class="section-title">
        <h2>Discussion</h2>
        <span class="muted"><?= count($comments) ?> comments</span>
    </div>
    <p class="muted">Read the discussion below. Commenting is available to the admin only.</p>
    <div class="list-stack">
        <?php foreach ($comments as $comment): ?>
            <div class="stack-item">
                <div class="meta">
                    <span><i class="fa-solid fa-user"></i> <?= e($comment['name']) ?></span>
                    <span><?= e(format_date($comment['created_at'])) ?></span>
                </div>
                <p><?= e($comment['comment']) ?></p>
            </div>
        <?php endforeach; ?>
        <?php if (!$comments): ?>
            <p class="muted">No comments yet. Start the discussion.</p>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
