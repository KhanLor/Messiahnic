<?php
require __DIR__ . '/bootstrap.php';

$pageTitle = 'Teachings';
$query = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$teachings = [];
$categories = ['Sabbath', 'Feast Days', 'Salvation', 'Torah', 'Prophets', 'Gospel', 'Faith', 'Prayer'];

try {
    $sql = 'SELECT id, title, content, category, scripture_reference, media_path, created_at FROM teachings WHERE 1=1';
    $params = [];

    if ($query !== '') {
        $sql .= ' AND (title LIKE ? OR content LIKE ? OR scripture_reference LIKE ?)';
        $wildcard = '%' . $query . '%';
        $params = array_merge($params, [$wildcard, $wildcard, $wildcard]);
    }

    if ($category !== '') {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }

    $sql .= ' ORDER BY created_at DESC, id DESC';
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $teachings = $statement->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Teachings could not be loaded yet.');
}

include __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="section-title">
        <h2>Yahushua teachings</h2>
        <?php if (auth_check() && (current_user()['role'] ?? 'believer') === 'admin'): ?>
            <a class="btn btn-primary" href="<?= e(app_url('admin/teachings.php')) ?>">Add teaching</a>
        <?php endif; ?>
    </div>

    <form class="panel form" method="get">
        <div class="field-grid">
            <label>
                Search
                <input type="search" name="q" value="<?= e($query) ?>" placeholder="Title, scripture, or keyword">
            </label>
            <label>
                Category
                <select name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $option): ?>
                        <option value="<?= e($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn btn-primary" type="submit">Filter</button>
    </form>
</section>

<section class="section grid cols-3">
    <?php foreach ($teachings as $teaching): ?>
        <article class="card">
            <div class="meta">
                <span class="badge"><?= e($teaching['category']) ?></span>
                <span><?= e($teaching['scripture_reference']) ?></span>
            </div>
            <h3><a href="<?= e(app_url('teaching.php?id=' . (int) $teaching['id'])) ?>"><?= e($teaching['title']) ?></a></h3>
            <p class="muted"><?= e(format_date($teaching['created_at'])) ?></p>
            <p><?= e(mb_strimwidth(strip_tags($teaching['content']), 0, 180, '...')) ?></p>
            <a class="btn btn-ghost" href="<?= e(app_url('teaching.php?id=' . (int) $teaching['id'])) ?>">Read more</a>
        </article>
    <?php endforeach; ?>
    <?php if (!$teachings): ?>
        <div class="card">No teachings match your search yet.</div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
