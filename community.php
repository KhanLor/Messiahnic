<?php
require __DIR__ . '/bootstrap.php';

$pageTitle = 'Live Broadcast';

$liveUrl = get_setting('facebook_live_url', '');
$embedUrl = facebook_live_embed_url($liveUrl);

include __DIR__ . '/includes/header.php';
?>
<section class="panel">
    <div class="section-title">
        <h2>Live broadcast</h2>
    </div>
    <?php if ($embedUrl): ?>
        <div class="live-video-panel">
            <div class="live-video-frame live-video-portrait-adapt">
                <iframe
                    src="<?= e($embedUrl) ?>"
                    title="Facebook Live Broadcast"
                    allowfullscreen
                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share">
                </iframe>
            </div>
        </div>
        <p class="muted" style="margin-top: 0.8rem;">Watching from Facebook Live.</p>
        <p><a class="btn btn-ghost" href="<?= e($liveUrl) ?>" target="_blank" rel="noopener">Open in Facebook</a></p>
    <?php else: ?>
        <p class="muted">No Facebook Live link has been added yet.</p>
        <?php if (current_user() && (current_user()['role'] ?? 'believer') === 'admin'): ?>
            <p><a class="btn btn-primary" href="<?= e(app_url('admin/community.php')) ?>">Set live broadcast URL</a></p>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
