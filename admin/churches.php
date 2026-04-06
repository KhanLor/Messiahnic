<?php
require __DIR__ . '/../bootstrap.php';
require_admin();
ensure_church_locations_table();

$pageTitle = 'Manage Churches';
$error = null;
$editingChurch = null;

function delete_church_photo(?string $photoPath): void
{
    $relative = trim((string) $photoPath);
    if ($relative === '') {
        return;
    }

    $absolute = __DIR__ . '/../' . ltrim(str_replace('\\', '/', $relative), '/');
    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

if (is_post()) {
    try {
        verify_csrf();
        $action = $_POST['action'] ?? 'create';

        if ($action === 'delete') {
            $churchId = (int) ($_POST['church_id'] ?? 0);
            $photoStatement = db()->prepare('SELECT photo_path FROM church_locations WHERE id = ? LIMIT 1');
            $photoStatement->execute([$churchId]);
            $existingPhotoPath = $photoStatement->fetchColumn() ?: null;

            $statement = db()->prepare('DELETE FROM church_locations WHERE id = ?');
            $statement->execute([$churchId]);

            delete_church_photo(is_string($existingPhotoPath) ? $existingPhotoPath : null);
            flash('success', 'Church location deleted.');
            redirect('admin/churches.php');
        }

        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $pastorName = trim($_POST['pastor_name'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '' || $address === '' || $city === '' || $latitude === '' || $longitude === '') {
            throw new RuntimeException('Name, address, city, latitude, and longitude are required.');
        }

        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            throw new RuntimeException('Latitude and longitude must be numeric values.');
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new RuntimeException('Latitude or longitude is out of valid range.');
        }

        if ($action === 'update') {
            $churchId = (int) ($_POST['church_id'] ?? 0);
            if ($churchId <= 0) {
                throw new RuntimeException('Church location not found for update.');
            }

            $existingStatement = db()->prepare('SELECT photo_path FROM church_locations WHERE id = ? LIMIT 1');
            $existingStatement->execute([$churchId]);
            $existingChurch = $existingStatement->fetch() ?: null;

            if (!$existingChurch) {
                throw new RuntimeException('Church location not found for update.');
            }

            $photoPath = $existingChurch['photo_path'] ?? null;

            if (!empty($_FILES['photo']['name'])) {
                $newPhotoPath = upload_file($_FILES['photo'], 'churches', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                if ($newPhotoPath) {
                    delete_church_photo($photoPath);
                    $photoPath = $newPhotoPath;
                }
            }

            if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
                delete_church_photo($photoPath);
                $photoPath = null;
            }

            $statement = db()->prepare('UPDATE church_locations SET name = ?, address = ?, city = ?, pastor_name = ?, photo_path = ?, latitude = ?, longitude = ?, contact = ?, description = ? WHERE id = ?');
            $statement->execute([$name, $address, $city, $pastorName ?: null, $photoPath, $lat, $lng, $contact ?: null, $description ?: null, $churchId]);
            flash('success', 'Church location updated.');
        } else {
            $photoPath = null;
            if (!empty($_FILES['photo']['name'])) {
                $photoPath = upload_file($_FILES['photo'], 'churches', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            }

            $statement = db()->prepare('INSERT INTO church_locations (name, address, city, pastor_name, photo_path, latitude, longitude, contact, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
            $statement->execute([$name, $address, $city, $pastorName ?: null, $photoPath, $lat, $lng, $contact ?: null, $description ?: null]);

            seed_notification_from_action(
                'New church location',
                'Church',
                $name . ' was added to the churches map.',
                'churches.php'
            );

            flash('success', 'Church location saved.');
        }

        clear_old();
        redirect('admin/churches.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        set_old();
    }
}

$churches = [];
try {
    $churches = db()->query('SELECT * FROM church_locations ORDER BY created_at DESC, id DESC')->fetchAll();
} catch (Throwable $exception) {
    flash('error', 'Church locations table is missing. Import the latest schema.sql to add it.');
}

$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0) {
    $statement = db()->prepare('SELECT * FROM church_locations WHERE id = ? LIMIT 1');
    $statement->execute([$editId]);
    $editingChurch = $statement->fetch() ?: null;

    if (!$editingChurch) {
        flash('error', 'Church location not found for editing.');
        redirect('admin/churches.php');
    }
}

$isEditMode = $editingChurch !== null;
$formAction = $isEditMode ? 'update' : 'create';
$modalTitle = $isEditMode ? 'Edit church location' : 'Save church location';
$submitLabel = $isEditMode ? 'Update church' : 'Save church';
$nameValue = old('name', $editingChurch['name'] ?? '');
$addressValue = old('address', $editingChurch['address'] ?? '');
$cityValue = old('city', $editingChurch['city'] ?? '');
$pastorValue = old('pastor_name', $editingChurch['pastor_name'] ?? '');
$contactValue = old('contact', $editingChurch['contact'] ?? '');
$latitudeValue = old('latitude', isset($editingChurch['latitude']) ? (string) $editingChurch['latitude'] : '');
$longitudeValue = old('longitude', isset($editingChurch['longitude']) ? (string) $editingChurch['longitude'] : '');
$descriptionValue = old('description', $editingChurch['description'] ?? '');
$photoValue = $editingChurch['photo_path'] ?? '';

include __DIR__ . '/../includes/header.php';
?>
<section class="grid cols-2">
    <section class="panel">
        <div class="section-title">
            <h2>Add church location</h2>
            <span class="badge">Map coordinates</span>
        </div>

        <button class="btn btn-primary" type="button" data-open-modal="churchModal">Open church form</button>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
    </section>

    <aside class="panel">
        <div class="section-title">
            <h2>Saved locations</h2>
            <span class="muted"><?= count($churches) ?> items</span>
        </div>
        <div class="list-stack">
            <?php foreach ($churches as $church): ?>
                <div class="stack-item">
                    <div class="meta">
                        <span class="badge"><?= e($church['city']) ?></span>
                        <span><?= e(format_date($church['created_at'])) ?></span>
                    </div>
                    <?php if (!empty($church['photo_path'])): ?>
                        <p><img src="<?= e(app_url($church['photo_path'])) ?>" alt="<?= e($church['name']) ?> photo" style="width: 100%; max-width: 320px; border-radius: 12px; border: 1px solid var(--border);"></p>
                    <?php endif; ?>
                    <strong><?= e($church['name']) ?></strong>
                    <?php if (!empty($church['pastor_name'])): ?>
                        <p><strong>Pastor:</strong> <?= e($church['pastor_name']) ?></p>
                    <?php endif; ?>
                    <p><?= e($church['address']) ?></p>
                    <p class="muted">Lat: <?= e((string) $church['latitude']) ?>, Lng: <?= e((string) $church['longitude']) ?></p>
                    <div class="actions">
                        <a class="btn btn-primary" href="<?= e(app_url('admin/churches.php?edit=' . (int) $church['id'])) ?>">Edit</a>
                    <form method="post" onsubmit="return confirm('Delete this church location?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="church_id" value="<?= (int) $church['id'] ?>">
                        <button class="btn btn-ghost" type="submit">Delete</button>
                    </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$churches): ?>
                <p class="muted">No church locations yet.</p>
            <?php endif; ?>
        </div>
    </aside>
</section>

<div class="modal <?= ($error || $isEditMode) ? 'modal-open' : '' ?>" data-modal="churchModal" aria-hidden="<?= ($error || $isEditMode) ? 'false' : 'true' ?>">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="churchModalTitle">
        <div class="section-title">
            <h2 id="churchModalTitle"><?= e($modalTitle) ?></h2>
            <button class="modal-close" type="button" data-close-modal aria-label="Close modal">×</button>
        </div>

        <form class="form" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="<?= e($formAction) ?>">
            <?php if ($isEditMode): ?>
                <input type="hidden" name="church_id" value="<?= (int) $editingChurch['id'] ?>">
            <?php endif; ?>
            <label>
                Church name
                <input type="text" name="name" value="<?= e($nameValue) ?>" required>
            </label>
            <label>
                Address
                <input type="text" name="address" value="<?= e($addressValue) ?>" required>
            </label>
            <div class="field-grid">
                <label>
                    City
                    <input type="text" name="city" value="<?= e($cityValue) ?>" required>
                </label>
                <label>
                    Pastor name
                    <input type="text" name="pastor_name" value="<?= e($pastorValue) ?>" placeholder="Optional">
                </label>
            </div>
            <div class="field-grid">
                <label>
                    Contact
                    <input type="text" name="contact" value="<?= e($contactValue) ?>" placeholder="Optional">
                </label>
                <label>
                    Latitude
                    <input type="text" name="latitude" value="<?= e($latitudeValue) ?>" placeholder="14.5995000" required>
                </label>
            </div>
            <div class="field-grid">
                <label>
                    Longitude
                    <input type="text" name="longitude" value="<?= e($longitudeValue) ?>" placeholder="120.9842000" required>
                </label>
                <div></div>
            </div>
            <label>
                Description
                <textarea name="description"><?= e($descriptionValue) ?></textarea>
            </label>
            <label>
                Church photo
                <input type="file" name="photo" accept="image/*">
            </label>
            <?php if ($isEditMode && $photoValue !== ''): ?>
                <p class="muted">Current photo:</p>
                <p><img src="<?= e(app_url($photoValue)) ?>" alt="Current church photo" style="width: 100%; max-width: 360px; border-radius: 12px; border: 1px solid var(--border);"></p>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
                    <input type="checkbox" name="remove_photo" value="1" style="width: auto;">
                    Remove current photo
                </label>
            <?php endif; ?>
            <div class="actions">
                <button class="btn btn-primary" type="submit"><?= e($submitLabel) ?></button>
                <?php if ($isEditMode): ?>
                    <a class="btn btn-ghost" href="<?= e(app_url('admin/churches.php')) ?>">Cancel edit</a>
                <?php endif; ?>
                <button class="btn btn-ghost" type="button" data-close-modal>Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
