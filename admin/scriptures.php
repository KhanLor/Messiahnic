<?php
require __DIR__ . '/../bootstrap.php';
require_admin();
ensure_yahushua_name_study_scriptures();

$pageTitle = 'Manage Scriptures';
$error = null;
$editingVerse = null;

if (is_post()) {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? 'create';

        if ($action === 'delete') {
            $verseId = (int) ($_POST['verse_id'] ?? 0);
            $statement = db()->prepare('DELETE FROM scriptures WHERE id = ?');
            $statement->execute([$verseId]);
            flash('success', 'Verse deleted.');
            redirect('admin/scriptures.php');
        }

        $verseId = (int) ($_POST['verse_id'] ?? 0);

        $book = trim($_POST['book'] ?? '');
        $chapter = (int) ($_POST['chapter'] ?? 0);
        $verse = trim($_POST['verse'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $group = trim($_POST['testament_group'] ?? '');
        $highlighted = isset($_POST['is_highlighted']) ? 1 : 0;

        if ($book === '' || $chapter <= 0 || $verse === '' || $content === '' || $group === '') {
            throw new RuntimeException('All scripture fields are required.');
        }

        if (!in_array($group, ['Torah', 'Prophets', 'Writings', 'Gospel'], true)) {
            throw new RuntimeException('Invalid scripture section.');
        }

        if ($action === 'update') {
            if ($verseId <= 0) {
                throw new RuntimeException('Verse not found for editing.');
            }

            $statement = db()->prepare('UPDATE scriptures SET book = ?, chapter = ?, verse = ?, content = ?, testament_group = ?, is_highlighted = ? WHERE id = ?');
            $statement->execute([$book, $chapter, $verse, $content, $group, $highlighted, $verseId]);
            flash('success', 'Scripture verse updated.');
        } else {
            $statement = db()->prepare('INSERT INTO scriptures (book, chapter, verse, content, testament_group, is_highlighted) VALUES (?, ?, ?, ?, ?, ?)');
            $statement->execute([$book, $chapter, $verse, $content, $group, $highlighted]);
            flash('success', 'Scripture verse added.');
        }

        clear_old();
        redirect('admin/scriptures.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        set_old();
    }
}

$scriptures = [];
try {
    $scriptures = db()->query('SELECT * FROM scriptures ORDER BY testament_group ASC, book ASC, chapter ASC, verse ASC')->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Scriptures could not be loaded yet.');
}

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $statement = db()->prepare('SELECT * FROM scriptures WHERE id = ? LIMIT 1');
    $statement->execute([$editId]);
    $editingVerse = $statement->fetch() ?: null;

    if (!$editingVerse) {
        flash('error', 'Verse not found for editing.');
        redirect('admin/scriptures.php');
    }
}

$isEditMode = $editingVerse !== null;
$formAction = $isEditMode ? 'update' : 'create';
$submitLabel = $isEditMode ? 'Update verse' : 'Save verse';
$bookValue = old('book', $editingVerse['book'] ?? '');
$chapterValue = old('chapter', isset($editingVerse['chapter']) ? (string) $editingVerse['chapter'] : '');
$verseValue = old('verse', $editingVerse['verse'] ?? '');
$contentValue = old('content', $editingVerse['content'] ?? '');
$groupValue = old('testament_group', $editingVerse['testament_group'] ?? 'Torah');
$highlightedValue = isset($_SESSION['old']['is_highlighted']) ? '1' : ((int) ($editingVerse['is_highlighted'] ?? 0) === 1 ? '1' : '0');

include __DIR__ . '/../includes/header.php';
?>
<section class="grid cols-2">
    <section class="panel">
        <div class="section-title">
            <h2><?= $isEditMode ? 'Edit scripture verse' : 'Add scripture verse' ?></h2>
            <span class="badge">Highlighted passages</span>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <button class="btn btn-primary" type="button" data-open-modal="scriptureModal"><?= $isEditMode ? 'Open edit form' : 'Open scripture form' ?></button>
    </section>

    <aside class="panel">
        <div class="section-title">
            <h2>Scripture list</h2>
            <span class="muted"><?= count($scriptures) ?> verses</span>
        </div>
        <div class="list-stack">
            <?php foreach ($scriptures as $verse): ?>
                <div class="stack-item">
                    <div class="meta">
                        <span class="badge"><?= e($verse['testament_group']) ?></span>
                        <?php if ((int) $verse['is_highlighted'] === 1): ?>
                            <span class="badge"><i class="fa-solid fa-star"></i> Highlighted</span>
                        <?php endif; ?>
                        <span><?= e($verse['book']) ?> <?= e((string) $verse['chapter']) ?>:<?= e($verse['verse']) ?></span>
                    </div>
                    <p><?= e($verse['content']) ?></p>
                    <div class="actions">
                        <a class="btn btn-primary" href="<?= e(app_url('admin/scriptures.php?edit=' . (int) $verse['id'])) ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this verse?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="verse_id" value="<?= (int) $verse['id'] ?>">
                            <button class="btn btn-ghost" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$scriptures): ?>
                <p class="muted">No verses added yet.</p>
            <?php endif; ?>
        </div>
    </aside>
</section>

<div class="modal <?= ($error || $isEditMode) ? 'modal-open' : '' ?>" data-modal="scriptureModal" aria-hidden="<?= ($error || $isEditMode) ? 'false' : 'true' ?>">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="scriptureModalTitle">
        <div class="section-title">
            <h2 id="scriptureModalTitle"><?= $isEditMode ? 'Edit scripture verse' : 'Add scripture verse' ?></h2>
            <button class="modal-close" type="button" data-close-modal aria-label="Close modal">×</button>
        </div>

        <form class="form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= e($formAction) ?>">
            <?php if ($isEditMode): ?>
                <input type="hidden" name="verse_id" value="<?= (int) $editingVerse['id'] ?>">
            <?php endif; ?>
            <div class="field-grid">
                <label>
                    Book
                    <input type="text" name="book" value="<?= e($bookValue) ?>" required>
                </label>
                <label>
                    Testament group
                    <select name="testament_group">
                        <option value="Torah" <?= $groupValue === 'Torah' ? 'selected' : '' ?>>Torah</option>
                        <option value="Prophets" <?= $groupValue === 'Prophets' ? 'selected' : '' ?>>Prophets</option>
                        <option value="Writings" <?= $groupValue === 'Writings' ? 'selected' : '' ?>>Writings</option>
                        <option value="Gospel" <?= $groupValue === 'Gospel' ? 'selected' : '' ?>>Gospel</option>
                    </select>
                </label>
            </div>
            <div class="field-grid">
                <label>
                    Chapter
                    <input type="number" name="chapter" min="1" value="<?= e($chapterValue) ?>" required>
                </label>
                <label>
                    Verse
                    <input type="text" name="verse" value="<?= e($verseValue) ?>" required>
                </label>
            </div>
            <label>
                Content
                <textarea name="content" required><?= e($contentValue) ?></textarea>
            </label>
            <label>
                <input type="checkbox" name="is_highlighted" value="1" <?= $highlightedValue === '1' ? 'checked' : '' ?>>
                Highlight this passage
            </label>
            <div class="actions">
                <button class="btn btn-primary" type="submit"><?= e($submitLabel) ?></button>
                <?php if ($isEditMode): ?>
                    <a class="btn btn-ghost" href="<?= e(app_url('admin/scriptures.php')) ?>">Cancel edit</a>
                <?php endif; ?>
                <button class="btn btn-ghost" type="button" data-close-modal>Close</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
