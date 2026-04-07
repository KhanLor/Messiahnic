<?php
require __DIR__ . '/bootstrap.php';
ensure_yahushua_name_study_scriptures();
ensure_daily_ai_savior_scripture();

$pageTitle = 'Scriptures';
$query = trim($_GET['q'] ?? '');
$group = trim($_GET['group'] ?? '');
$scriptures = [];
$groups = ['Torah', 'Prophets', 'Writings', 'Gospel'];

try {
    $sql = 'SELECT * FROM scriptures WHERE 1=1';
    $params = [];

    if ($query !== '') {
        $sql .= ' AND (book LIKE ? OR content LIKE ? OR verse LIKE ?)';
        $wildcard = '%' . $query . '%';
        $params = [$wildcard, $wildcard, $wildcard];
    }

    if ($group !== '') {
        $sql .= ' AND testament_group = ?';
        $params[] = $group;
    }

    $sql .= ' ORDER BY book ASC, chapter ASC, verse ASC LIMIT 200';
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $scriptures = $statement->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Scriptures could not be loaded yet.');
}

include __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="section-title">
        <h2>Scripture library</h2>
        <span class="muted">Torah, Prophets, Writings, and Gospel</span>
    </div>
    <form class="panel form" method="get">
        <div class="field-grid">
            <label>
                Search verses
                <input type="search" name="q" value="<?= e($query) ?>" placeholder="Book, verse, or phrase">
            </label>
            <label>
                Section
                <select name="group">
                    <option value="">All sections</option>
                    <?php foreach ($groups as $option): ?>
                        <option value="<?= e($option) ?>" <?= $group === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn btn-primary" type="submit">Search</button>
    </form>
</section>

<section class="section list-stack">
    <?php foreach ($scriptures as $verse): ?>
        <article class="card">
            <div class="meta">
                <span class="badge"><?= e($verse['testament_group']) ?></span>
                <?php if ((int) $verse['is_highlighted'] === 1): ?>
                    <span class="badge"><i class="fa-solid fa-star"></i> Highlighted</span>
                <?php endif; ?>
                <span><?= e($verse['book']) ?> <?= e((string) $verse['chapter']) ?>:<?= e($verse['verse']) ?></span>
            </div>
            <p><?= e($verse['content']) ?></p>
        </article>
    <?php endforeach; ?>
    <?php if (!$scriptures): ?>
        <div class="card">No verses found.</div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
