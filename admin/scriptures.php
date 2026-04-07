<?php
require __DIR__ . '/../bootstrap.php';
require_admin();
ensure_yahushua_name_study_scriptures();
ensure_daily_ai_savior_scripture();

$pageTitle = 'Manage Scriptures';
$error = null;

if (is_post()) {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? 'create';

        if ($action === 'generate_daily') {
            ensure_daily_ai_savior_scripture();
            flash('success', 'Today\'s 10 AI Yahushua-focused verses are ready.');
            redirect('admin/scriptures.php');
        }

        if ($action === 'delete') {
            $verseId = (int) ($_POST['verse_id'] ?? 0);
            $statement = db()->prepare('DELETE FROM scriptures WHERE id = ?');
            $statement->execute([$verseId]);
            flash('success', 'Verse deleted.');
            redirect('admin/scriptures.php');
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

include __DIR__ . '/../includes/header.php';
?>
<section class="grid cols-2">
    <section class="panel">
        <div class="section-title">
            <h2>AI scripture every day</h2>
            <span class="badge">Yahushua-focused verses</span>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <p class="muted" style="margin-bottom: 1rem;">
            10 verses are generated automatically each day with a focus on why Yahushua is our true Savior.
            Manual typing is disabled on this page.
        </p>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="generate_daily">
            <button class="btn btn-primary" type="submit">Generate today's 10 AI verses</button>
        </form>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
