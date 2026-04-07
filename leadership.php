<?php
require __DIR__ . '/bootstrap.php';

$pageTitle = 'Leadership';

$leaderName = get_setting('leadership_name', 'To be announced');
$leaderAddress = get_setting('leadership_address', 'To be announced');
$leaderContactNumber = get_setting('leadership_contact_number', 'To be announced');
$leaderFacebookPage = get_setting('leadership_facebook_page', '');
$leaderPicture = get_setting('leadership_picture', '');
$executiveMembersRaw = get_setting('leadership_executive_members', '');
$decodedExecutiveMembers = json_decode($executiveMembersRaw, true);
$executiveMembers = [];

if (is_array($decodedExecutiveMembers)) {
    foreach ($decodedExecutiveMembers as $member) {
        if (is_array($member)) {
            $name = trim((string) ($member['name'] ?? ''));
            $contactNumber = trim((string) ($member['contact_number'] ?? ''));
            $facebookPage = trim((string) ($member['facebook_page'] ?? ''));
            $picture = trim((string) ($member['picture'] ?? ''));
            if ($name === '' && $contactNumber === '' && $facebookPage === '' && $picture === '') {
                continue;
            }
            $executiveMembers[] = [
                'name' => $name,
                'contact_number' => $contactNumber,
                'facebook_page' => $facebookPage,
                'picture' => $picture,
            ];
            continue;
        }

        if (is_string($member) && trim($member) !== '') {
            $executiveMembers[] = [
                'name' => trim($member),
                'contact_number' => '',
                'facebook_page' => '',
                'picture' => '',
            ];
        }
    }
} else {
    $legacyNames = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $executiveMembersRaw ?: ''))));
    foreach ($legacyNames as $legacyName) {
        $executiveMembers[] = [
            'name' => $legacyName,
            'contact_number' => '',
            'facebook_page' => '',
            'picture' => '',
        ];
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="section panel">
    <div class="section-title">
        <h2>Leadership</h2>
        <span class="badge">Congregation leader</span>
    </div>

    <p class="muted" style="margin-bottom: 1rem;">
        Leadership in the Messiahnic Believers congregation serves in humility, guiding the church in faith,
        prayer, and obedience to the teachings of the Scriptures.
    </p>

    <div class="grid cols-2">
        <div class="card" style="display: flex; align-items: center; justify-content: center; min-height: 320px;">
            <?php if ($leaderPicture): ?>
                <img src="<?= e(app_url($leaderPicture)) ?>" alt="<?= e($leaderName) ?>" style="width: 100%; max-width: 360px; height: auto; border-radius: 16px; object-fit: cover;">
            <?php else: ?>
                <p class="muted">No leader picture uploaded yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Leader profile</h3>
            <p><strong>Name:</strong> <?= e($leaderName) ?></p>
            <p><strong>Address:</strong> <?= e($leaderAddress) ?></p>
            <p><strong>Contact Number:</strong> <?= e($leaderContactNumber) ?></p>
            <?php if ($leaderFacebookPage): ?>
                <p><a href="<?= e($leaderFacebookPage) ?>" target="_blank" rel="noopener" aria-label="Facebook page"><i class="fa-brands fa-facebook"></i></a></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section panel">
    <div class="section-title">
        <h2>Our Executive Members</h2>
        <span class="muted">Supporting the congregation leadership</span>
    </div>

    <?php if ($executiveMembers): ?>
        <div class="grid cols-3">
            <?php foreach ($executiveMembers as $member): ?>
                <div class="card">
                    <?php if (!empty($member['picture'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <img src="<?= e(app_url((string) $member['picture'])) ?>" alt="<?= e((string) ($member['name'] ?: 'Executive member')) ?>" style="width: 100%; max-height: 220px; object-fit: cover; border-radius: 12px;">
                        </div>
                    <?php endif; ?>
                    <h3><?= e((string) ($member['name'] ?: 'Executive Member')) ?></h3>
                    <?php if (!empty($member['contact_number'])): ?>
                        <p><strong>Contact Number:</strong> <?= e((string) $member['contact_number']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($member['facebook_page'])): ?>
                        <p><a href="<?= e((string) $member['facebook_page']) ?>" target="_blank" rel="noopener" aria-label="Executive member Facebook page"><i class="fa-brands fa-facebook"></i></a></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="muted">No executive members added yet.</p>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>