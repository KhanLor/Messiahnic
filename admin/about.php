<?php
require __DIR__ . '/../bootstrap.php';
require_admin();

$pageTitle = 'Manage About Page';
$error = null;

if (is_post()) {
    try {
        verify_csrf();

        $congregationName = trim($_POST['congregation_name'] ?? '');
        $congregationAddress = trim($_POST['congregation_address'] ?? '');
        $congregationContactNumber = trim($_POST['congregation_contact_number'] ?? '');
        $congregationFacebookPage = trim($_POST['congregation_facebook_page'] ?? '');

        if ($congregationName === '' || $congregationAddress === '' || $congregationContactNumber === '') {
            throw new RuntimeException('All fields are required.');
        }

        // Handle picture upload
        $picturePath = get_setting('congregation_picture', '');
        if (!empty($_FILES['congregation_picture']['name'])) {
            $newPicturePath = upload_file($_FILES['congregation_picture'], 'congregation', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            if ($newPicturePath) {
                // Delete old picture if exists
                if ($picturePath && file_exists(__DIR__ . '/../' . $picturePath)) {
                    @unlink(__DIR__ . '/../' . $picturePath);
                }
                $picturePath = $newPicturePath;
            }
        }

        if (isset($_POST['remove_picture']) && $_POST['remove_picture'] === '1') {
            if ($picturePath && file_exists(__DIR__ . '/../' . $picturePath)) {
                @unlink(__DIR__ . '/../' . $picturePath);
            }
            $picturePath = '';
        }

        set_setting('congregation_name', $congregationName);
        set_setting('congregation_address', $congregationAddress);
        set_setting('congregation_contact_number', $congregationContactNumber);
        set_setting('congregation_facebook_page', $congregationFacebookPage);
        set_setting('congregation_picture', $picturePath);

        flash('success', 'About page updated.');
        redirect('admin/about.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$congregationNameValue = get_setting('congregation_name', '');
$congregationAddressValue = get_setting('congregation_address', '');
$congregationContactNumberValue = get_setting('congregation_contact_number', '');
$congregationFacebookPageValue = get_setting('congregation_facebook_page', '');
$presidentValue = get_setting('congregation_president', 'To be announced');
$executiveBoardValue = get_setting('congregation_executive_board', 'To be announced');
$pictureValue = get_setting('congregation_picture', '');

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="section-title">
        <h2>About page settings</h2>
        <span class="muted">Edit congregation info and picture</span>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="form" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        
        <label>
            Congregation Name
            <input type="text" name="congregation_name" value="<?= e($congregationNameValue) ?>" required>
        </label>

        <label>
            Address
            <input type="text" name="congregation_address" value="<?= e($congregationAddressValue) ?>" required>
        </label>

        <label>
            Contact Number
            <input type="tel" name="congregation_contact_number" value="<?= e($congregationContactNumberValue) ?>" required>
        </label>

        <label>
            Facebook Page
            <input type="url" name="congregation_facebook_page" value="<?= e($congregationFacebookPageValue) ?>" placeholder="https://facebook.com/yourpage">
        </label>

        <label>
            Picture
            <input type="file" name="congregation_picture" accept="image/*">
        </label>
        <?php if ($pictureValue): ?>
            <div style="margin: 1rem 0;">
                <img src="<?= e(app_url($pictureValue)) ?>" alt="Congregation picture" style="max-width: 200px; border-radius: 8px;"/>
                <label style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="remove_picture" value="1">
                    Remove current picture
                </label>
            </div>
        <?php endif; ?>
        <div class="actions">
            <button class="btn btn-primary" type="submit">Save About page</button>
            <a class="btn btn-ghost" href="<?= e(app_url('about.php')) ?>" target="_blank" rel="noopener">View public page</a>
        </div>
    </form>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>