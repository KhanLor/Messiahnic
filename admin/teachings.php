<?php
require __DIR__ . '/../bootstrap.php';
require_admin();

$pageTitle = 'Manage Teachings';
$error = null;
$editingTeaching = null;

function is_youtube_url(string $url): bool
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '') {
        return false;
    }

    $allowedHosts = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be'];
    return in_array($host, $allowedHosts, true);
}

function normalize_url(?string $value): ?string
{
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

if (is_post()) {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? 'create';

        if ($action === 'delete') {
            $teachingId = (int) ($_POST['teaching_id'] ?? 0);
            $lookup = db()->prepare('SELECT media_path FROM teachings WHERE id = ? LIMIT 1');
            $lookup->execute([$teachingId]);
            $teaching = $lookup->fetch();

            if (!empty($teaching['media_path'])) {
                $isLocalFile = parse_url((string) $teaching['media_path'], PHP_URL_SCHEME) === null;
                if ($isLocalFile) {
                    $absolutePath = __DIR__ . '/../' . ltrim((string) $teaching['media_path'], '/');
                    if (is_file($absolutePath)) {
                        unlink($absolutePath);
                    }
                }
            }

            $statement = db()->prepare('DELETE FROM teachings WHERE id = ?');
            $statement->execute([$teachingId]);
            flash('success', 'Teaching deleted.');
            redirect('admin/teachings.php');
        }

        $teachingId = (int) ($_POST['teaching_id'] ?? 0);

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $scriptureReference = trim($_POST['scripture_reference'] ?? '');

        if ($title === '' || $content === '' || $category === '' || $scriptureReference === '') {
            throw new RuntimeException('All teaching fields are required.');
        }

        $youtubeUrl = normalize_url($_POST['youtube_url'] ?? null);
        if ($youtubeUrl === null) {
            throw new RuntimeException('Please paste a YouTube video URL.');
        }

        if (!is_youtube_url($youtubeUrl)) {
            throw new RuntimeException('Please enter a valid YouTube URL.');
        }

        $mediaPath = null;
        $mediaType = null;
        if ($action === 'update' && $teachingId > 0) {
            $lookup = db()->prepare('SELECT media_path, media_type FROM teachings WHERE id = ? LIMIT 1');
            $lookup->execute([$teachingId]);
            $existing = $lookup->fetch();
            $mediaPath = $existing['media_path'] ?? null;
            $mediaType = $existing['media_type'] ?? null;
        }

        $mediaPath = $youtubeUrl;
        $mediaType = 'youtube';

        if ($action === 'update') {
            if ($teachingId <= 0) {
                throw new RuntimeException('Teaching not found for editing.');
            }

            $statement = db()->prepare('UPDATE teachings SET title = ?, content = ?, category = ?, scripture_reference = ?, media_path = ?, media_type = ? WHERE id = ?');
            $statement->execute([$title, $content, $category, $scriptureReference, $mediaPath, $mediaType, $teachingId]);
            flash('success', 'Teaching updated.');
        } else {
            $statement = db()->prepare('INSERT INTO teachings (title, content, category, scripture_reference, media_path, media_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $statement->execute([$title, $content, $category, $scriptureReference, $mediaPath, $mediaType]);
            $newId = (int) db()->lastInsertId();

            seed_notification_from_action(
                $title,
                'Teaching',
                'A new teaching has been published in the ' . $category . ' category.',
                'teaching.php?id=' . $newId
            );
            flash('success', 'Teaching published.');
        }

        clear_old();
        redirect('admin/teachings.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        set_old();
    }
}

$teachings = [];
try {
    $teachings = db()->query('SELECT id, title, category, scripture_reference, created_at FROM teachings ORDER BY created_at DESC, id DESC')->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Teachings could not be loaded yet.');
}

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $statement = db()->prepare('SELECT * FROM teachings WHERE id = ? LIMIT 1');
    $statement->execute([$editId]);
    $editingTeaching = $statement->fetch() ?: null;

    if (!$editingTeaching) {
        flash('error', 'Teaching not found for editing.');
        redirect('admin/teachings.php');
    }
}

$isEditMode = $editingTeaching !== null;
$formAction = $isEditMode ? 'update' : 'create';
$submitLabel = $isEditMode ? 'Update teaching' : 'Publish teaching';
$titleValue = old('title', $editingTeaching['title'] ?? '');
$contentValue = old('content', $editingTeaching['content'] ?? '');
$categoryValue = old('category', $editingTeaching['category'] ?? '');
$referenceValue = old('scripture_reference', $editingTeaching['scripture_reference'] ?? '');
$youtubeValue = old(
    'youtube_url',
    (!empty($editingTeaching['media_path']) && (($editingTeaching['media_type'] ?? '') === 'youtube' || is_youtube_url((string) $editingTeaching['media_path'])))
        ? $editingTeaching['media_path']
        : ''
);

include __DIR__ . '/../includes/header.php';
?>
<section class="grid cols-2">
    <section class="panel">
        <div class="section-title">
            <h2><?= $isEditMode ? 'Edit teaching' : 'Add teaching' ?></h2>
            <span class="badge">Admin only</span>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <button class="btn btn-primary" type="button" data-open-modal="teachingModal"><?= $isEditMode ? 'Open edit form' : 'Open teaching form' ?></button>
    </section>

    <aside class="panel">
        <div class="section-title">
            <h2>Existing teachings</h2>
            <span class="muted"><?= count($teachings) ?> items</span>
        </div>
        <div class="list-stack">
            <?php foreach ($teachings as $teaching): ?>
                <div class="stack-item">
                    <div class="meta">
                        <span class="badge"><?= e($teaching['category']) ?></span>
                        <span><?= e($teaching['scripture_reference']) ?></span>
                    </div>
                    <strong><?= e($teaching['title']) ?></strong>
                    <p class="muted"><?= e(format_date($teaching['created_at'])) ?></p>
                    <div class="actions">
                        <a class="btn btn-primary" href="<?= e(app_url('admin/teachings.php?edit=' . (int) $teaching['id'])) ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this teaching?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="teaching_id" value="<?= (int) $teaching['id'] ?>">
                            <button class="btn btn-ghost" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$teachings): ?>
                <p class="muted">No teachings created yet.</p>
            <?php endif; ?>
        </div>
    </aside>
</section>

<div class="modal <?= ($error || $isEditMode) ? 'modal-open' : '' ?>" data-modal="teachingModal" aria-hidden="<?= ($error || $isEditMode) ? 'false' : 'true' ?>">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="teachingModalTitle">
        <div class="section-title">
            <h2 id="teachingModalTitle"><?= $isEditMode ? 'Edit teaching' : 'Add teaching' ?></h2>
            <button class="modal-close" type="button" data-close-modal aria-label="Close modal">×</button>
        </div>

        <form class="form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= e($formAction) ?>">
            <?php if ($isEditMode): ?>
                <input type="hidden" name="teaching_id" value="<?= (int) $editingTeaching['id'] ?>">
            <?php endif; ?>
            <label>
                Title
                <input type="text" name="title" value="<?= e($titleValue) ?>" required>
            </label>
            <label>
                Content
                <textarea name="content" required><?= e($contentValue) ?></textarea>
            </label>
            <div class="field-grid">
                <label>
                    Category
                    <input type="text" name="category" value="<?= e($categoryValue) ?>" placeholder="Sabbath, Feast Days, Salvation" required>
                </label>
                <label>
                    Scripture reference
                    <input type="text" name="scripture_reference" value="<?= e($referenceValue) ?>" placeholder="Torah, Prophets, Gospel" required>
                </label>
            </div>
            <label>
                Paste YouTube video link
                <div class="field-inline">
                    <input type="url" name="youtube_url" id="youtube_url" value="<?= e($youtubeValue) ?>" placeholder="https://www.youtube.com/watch?v=..." required>
                    <button class="btn btn-ghost" type="button" id="pasteYouTubeLink">Paste link</button>
                </div>
                <small class="muted">Copy a YouTube URL, then click Paste link.</small>
            </label>
            <div class="actions">
                <button class="btn btn-primary" type="submit"><?= e($submitLabel) ?></button>
                <?php if ($isEditMode): ?>
                    <a class="btn btn-ghost" href="<?= e(app_url('admin/teachings.php')) ?>">Cancel edit</a>
                <?php endif; ?>
                <button class="btn btn-ghost" type="button" data-close-modal>Close</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('click', async function (event) {
    if (event.target && event.target.id === 'pasteYouTubeLink') {
        const input = document.getElementById('youtube_url');
        if (!input || !navigator.clipboard || !navigator.clipboard.readText) {
            return;
        }

        try {
            const text = await navigator.clipboard.readText();
            input.value = text.trim();
            input.focus();
        } catch (error) {
            // Clipboard access can be blocked by browser permissions.
        }
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
