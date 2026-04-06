<?php
require __DIR__ . '/../bootstrap.php';
require_admin();

$pageTitle = 'Manage Live Broadcast';
$error = null;

$currentUrl = get_setting('facebook_live_url', '');

if (is_post()) {
    try {
        verify_csrf();
        $liveUrl = trim($_POST['facebook_live_url'] ?? '');

        if ($liveUrl !== '' && facebook_live_embed_url($liveUrl) === null) {
            throw new RuntimeException('Please enter a valid Facebook or fb.watch live video URL.');
        }

        set_setting('facebook_live_url', $liveUrl);
        $currentUrl = $liveUrl;
        flash('success', 'Live broadcast URL saved.');

        clear_old();
        redirect('admin/community.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        set_old();
    }
}

$urlValue = old('facebook_live_url', $currentUrl);
$embedPreview = facebook_live_embed_url($urlValue);

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="section-title">
        <h2>Live broadcast settings</h2>
        <span class="muted">Paste your Facebook Live URL here</span>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <button class="btn btn-primary" type="button" data-open-modal="liveBroadcastModal">Open live URL form</button>

    <?php if ($embedPreview): ?>
        <div class="live-video-panel">
            <div class="live-video-frame live-video-portrait-adapt">
                <iframe
                    src="<?= e($embedPreview) ?>"
                    title="Facebook Live Preview"
                    allowfullscreen
                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
                </iframe>
            </div>
        </div>
    <?php else: ?>
        <p class="muted">Add a valid Facebook Live URL to preview it here.</p>
    <?php endif; ?>
</section>

<div class="modal <?= $error ? 'modal-open' : '' ?>" data-modal="liveBroadcastModal" aria-hidden="<?= $error ? 'false' : 'true' ?>">
    <div class="modal-backdrop" data-close-modal></div>
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="liveBroadcastModalTitle">
        <div class="section-title">
            <h2 id="liveBroadcastModalTitle">Live broadcast settings</h2>
            <button class="modal-close" type="button" data-close-modal aria-label="Close modal">×</button>
        </div>

        <form class="form" method="post" style="margin-bottom: 1rem;">
            <?= csrf_field() ?>
            <label>
                Facebook Live URL
                <input
                    type="url"
                    name="facebook_live_url"
                    placeholder="https://www.facebook.com/... or https://fb.watch/..."
                    value="<?= e($urlValue) ?>">
            </label>
            <p class="muted" style="margin-top: -0.35rem;">Paste the URL you copy from your Facebook Live post.</p>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Save live URL</button>
                <button class="btn btn-ghost" type="button" data-close-modal>Close</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
