<?php
require __DIR__ . '/../bootstrap.php';
require_admin();

$pageTitle = 'Manage Events';
$error = null;
$editingEvent = null;

if (is_post()) {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? 'create';

        if ($action === 'delete') {
            $eventId = (int) ($_POST['event_id'] ?? 0);
            $statement = db()->prepare('DELETE FROM events WHERE id = ?');
            $statement->execute([$eventId]);
            flash('success', 'Event deleted.');
            redirect('admin/events.php');
        }

        $eventId = (int) ($_POST['event_id'] ?? 0);

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $type = trim($_POST['type'] ?? 'Event');

        if ($title === '' || $description === '' || $date === '') {
            throw new RuntimeException('All event fields are required.');
        }

        if (!in_array($type, ['Sabbath', 'Feast Day', 'Event'], true)) {
            throw new RuntimeException('Invalid event type.');
        }

        if ($action === 'update') {
            if ($eventId <= 0) {
                throw new RuntimeException('Event not found for editing.');
            }

            $statement = db()->prepare('UPDATE events SET title = ?, description = ?, date = ?, type = ? WHERE id = ?');
            $statement->execute([$title, $description, $date, $type, $eventId]);
            flash('success', 'Event updated.');
        } else {
            $statement = db()->prepare('INSERT INTO events (title, description, date, type, created_at) VALUES (?, ?, ?, ?, NOW())');
            $statement->execute([$title, $description, $date, $type]);
            $eventId = (int) db()->lastInsertId();

            seed_notification_from_action(
                $title,
                'Event',
                'A new ' . $type . ' has been scheduled for ' . $date . '.',
                'events.php'
            );

            flash('success', 'Event published.');
        }

        clear_old();
        redirect('admin/events.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        set_old();
    }
}

$events = [];
try {
    $events = db()->query('SELECT * FROM events ORDER BY date ASC, id DESC')->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Events could not be loaded yet.');
}

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $statement = db()->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $statement->execute([$editId]);
    $editingEvent = $statement->fetch() ?: null;

    if (!$editingEvent) {
        flash('error', 'Event not found for editing.');
        redirect('admin/events.php');
    }
}

$isEditMode = $editingEvent !== null;
$formAction = $isEditMode ? 'update' : 'create';
$submitLabel = $isEditMode ? 'Update event' : 'Save event';
$titleValue = old('title', $editingEvent['title'] ?? '');
$descriptionValue = old('description', $editingEvent['description'] ?? '');
$dateValue = old('date', $editingEvent['date'] ?? '');
$typeValue = old('type', $editingEvent['type'] ?? 'Event');

include __DIR__ . '/../includes/header.php';
?>
<section class="grid cols-2">
    <section class="panel">
        <div class="section-title">
            <h2><?= $isEditMode ? 'Edit event' : 'Add event' ?></h2>
            <span class="badge">Sabbath / Feast Day</span>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <button class="btn btn-primary" type="button" data-open-modal="eventModal"><?= $isEditMode ? 'Open edit form' : 'Open event form' ?></button>
    </section>

    <aside class="panel">
        <div class="section-title">
            <h2>Existing events</h2>
            <span class="muted"><?= count($events) ?> items</span>
        </div>
        <div class="list-stack">
            <?php foreach ($events as $event): ?>
                <div class="stack-item">
                    <div class="meta">
                        <span class="badge"><?= e($event['type']) ?></span>
                        <span><?= e(format_date($event['date'], 'M j, Y')) ?></span>
                    </div>
                    <strong><?= e($event['title']) ?></strong>
                    <p><?= e($event['description']) ?></p>
                    <div class="actions">
                        <a class="btn btn-primary" href="<?= e(app_url('admin/events.php?edit=' . (int) $event['id'])) ?>">Edit</a>
                        <form method="post" onsubmit="return confirm('Delete this event?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                            <button class="btn btn-ghost" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$events): ?>
                <p class="muted">No events created yet.</p>
            <?php endif; ?>
        </div>
    </aside>
</section>

<div class="modal <?= ($error || $isEditMode) ? 'modal-open' : '' ?>" data-modal="eventModal" aria-hidden="<?= ($error || $isEditMode) ? 'false' : 'true' ?>">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="eventModalTitle">
        <div class="section-title">
            <h2 id="eventModalTitle"><?= $isEditMode ? 'Edit event' : 'Add event' ?></h2>
            <button class="modal-close" type="button" data-close-modal aria-label="Close modal">×</button>
        </div>

        <form class="form" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= e($formAction) ?>">
            <?php if ($isEditMode): ?>
                <input type="hidden" name="event_id" value="<?= (int) $editingEvent['id'] ?>">
            <?php endif; ?>
            <label>
                Title
                <input type="text" name="title" value="<?= e($titleValue) ?>" required>
            </label>
            <label>
                Description
                <textarea name="description" required><?= e($descriptionValue) ?></textarea>
            </label>
            <div class="field-grid">
                <label>
                    Date
                    <input type="date" name="date" value="<?= e($dateValue) ?>" required>
                </label>
                <label>
                    Type
                    <select name="type">
                        <option value="Sabbath" <?= $typeValue === 'Sabbath' ? 'selected' : '' ?>>Sabbath</option>
                        <option value="Feast Day" <?= $typeValue === 'Feast Day' ? 'selected' : '' ?>>Feast Day</option>
                        <option value="Event" <?= $typeValue === 'Event' ? 'selected' : '' ?>>Event</option>
                    </select>
                </label>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit"><?= e($submitLabel) ?></button>
                <?php if ($isEditMode): ?>
                    <a class="btn btn-ghost" href="<?= e(app_url('admin/events.php')) ?>">Cancel edit</a>
                <?php endif; ?>
                <button class="btn btn-ghost" type="button" data-close-modal>Close</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
