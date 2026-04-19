<?php
require __DIR__ . '/bootstrap.php';

$teachingId = (int) ($_GET['id'] ?? 0);
$pageTitle = 'Teaching';
$error = null;
ensure_comments_table();

function extract_youtube_video_id(string $url): ?string
{
    if ($url === '') {
        return null;
    }

    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '') {
        return null;
    }

    if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        return $path !== '' ? $path : null;
    }

    if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        if (!empty($query['v'])) {
            return (string) $query['v'];
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        if (str_starts_with($path, 'embed/')) {
            return substr($path, 6) ?: null;
        }
    }

    return null;
}

if ($teachingId <= 0) {
    flash('error', 'Teaching not found.');
    redirect('teachings.php');
}

if (is_post()) {
    try {
        verify_csrf();

        $commentBody = trim($_POST['comment'] ?? '');
        if ($commentBody === '') {
            throw new RuntimeException('Please write a comment before submitting.');
        }

        $user = current_user();
        $userId = null;
        $guestName = null;
        $guestEmail = null;

        if ($user) {
            $userId = (int) $user['id'];
        } else {
            $guestName = trim($_POST['guest_name'] ?? '');
            $guestEmail = trim($_POST['guest_email'] ?? '');

            if ($guestName === '') {
                throw new RuntimeException('Please provide your name.');
            }

            if ($guestEmail !== '' && filter_var($guestEmail, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException('Please provide a valid email address or leave it blank.');
            }
        }

        $insertComment = db()->prepare(
            'INSERT INTO comments (user_id, teaching_id, guest_name, guest_email, comment, status, created_at) VALUES (?, ?, ?, ?, ?, "pending", NOW())'
        );
        $insertComment->execute([$userId, $teachingId, $guestName, $guestEmail, $commentBody]);

        seed_notification_from_action(
            'New teaching comment',
            'Comment',
            'A new teaching comment was submitted and is waiting for approval.',
            'admin/comments.php'
        );

        clear_old();
        flash('success', 'Your comment was submitted and is waiting for admin approval.');
        redirect('teaching.php?id=' . $teachingId);
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        set_old();
    }
}

try {
    $statement = db()->prepare('SELECT * FROM teachings WHERE id = ? LIMIT 1');
    $statement->execute([$teachingId]);
    $teaching = $statement->fetch();

    if (!$teaching) {
        flash('error', 'Teaching not found.');
        redirect('teachings.php');
    }

    $comments = db()->prepare(
        'SELECT c.*, u.name, u.photo
         FROM comments c
         LEFT JOIN users u ON u.id = c.user_id
         WHERE c.teaching_id = ? AND c.status = "approved"
         ORDER BY c.created_at DESC, c.id DESC'
    );
    $comments->execute([$teachingId]);
    $comments = $comments->fetchAll();
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

include __DIR__ . '/includes/header.php';

$mediaPath = (string) ($teaching['media_path'] ?? '');
$youtubeVideoId = extract_youtube_video_id($mediaPath);
$isExternalMedia = $mediaPath !== '' && parse_url($mediaPath, PHP_URL_SCHEME) !== null;
?>
<section class="grid cols-2">
    <article class="panel">
        <?php if ($youtubeVideoId !== null): ?>
            <div class="badge"><i class="fa-brands fa-youtube"></i> YouTube video</div>
            <div style="position: relative; width: 100%; padding-top: 56.25%; margin-top: 0.8rem; border-radius: 14px; overflow: hidden;">
                <iframe
                    src="<?= e('https://www.youtube.com/embed/' . $youtubeVideoId) ?>"
                    title="Teaching video"
                    style="position: absolute; inset: 0; width: 100%; height: 100%; border: 0;"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                ></iframe>
            </div>
        <?php elseif ($mediaPath !== ''): ?>
            <div class="badge"><i class="fa-solid fa-photo-film"></i> Media attached</div>
            <p><a href="<?= e($isExternalMedia ? $mediaPath : app_url($mediaPath)) ?>" target="_blank" rel="noreferrer">Open attached file</a></p>
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
        <form class="form" method="post">
            <?= csrf_field() ?>
            <?php if (!auth_check()): ?>
                <label>
                    Your name
                    <input type="text" name="guest_name" value="<?= e(old('guest_name')) ?>" required>
                </label>
                <label>
                    Email (optional)
                    <input type="email" name="guest_email" value="<?= e(old('guest_email')) ?>" placeholder="you@example.com">
                </label>
            <?php else: ?>
                <p class="muted">Commenting as <?= e((string) (current_user()['name'] ?? 'Believer')) ?>.</p>
            <?php endif; ?>
            <label>
                Your comment
                <textarea name="comment" required><?= e(old('comment')) ?></textarea>
            </label>
            <button class="btn btn-primary" type="submit">Post comment</button>
        </form>
        <p class="muted">Comments are reviewed by admin before they appear in the discussion.</p>
    </aside>
</section>

<section class="section panel">
    <div class="section-title">
        <h2>Discussion</h2>
        <span class="muted"><?= count($comments) ?> comments</span>
    </div>
    <p class="muted">Showing approved comments.</p>
    <div class="list-stack">
        <?php foreach ($comments as $comment): ?>
            <div class="stack-item">
                <div class="meta">
                    <span><i class="fa-solid fa-user"></i> <?= e((string) ($comment['name'] ?? $comment['guest_name'] ?? 'Guest')) ?></span>
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
