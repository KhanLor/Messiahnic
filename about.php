<?php
require __DIR__ . '/bootstrap.php';

$pageTitle = 'About Messiahnic Believers';

// Congregation info
$congregationName = get_setting('congregation_name', 'Messiahnic Believers Congregation');
$congregationAddress = get_setting('congregation_address', 'To be announced');
$congregationContactNumber = get_setting('congregation_contact_number', 'To be announced');
$congregationFacebookPage = get_setting('congregation_facebook_page', '');
$congregationPicture = get_setting('congregation_picture', '');

include __DIR__ . '/includes/header.php';
?>
<section class="section panel">
    <div class="section-title">
        <h2>About Messiahnic Believers</h2>
        <span class="badge">Faith, fellowship, and leadership</span>
    </div>

    <div class="grid cols-2">
        <div class="card" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 320px;">
            <?php if ($congregationPicture): ?>
                <img src="<?= e(app_url($congregationPicture)) ?>" alt="<?= e($congregationName) ?>" style="width: 100%; max-width: 420px; height: auto; border-radius: 16px; object-fit: cover;">
            <?php else: ?>
                <p class="muted">No congregation picture uploaded yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3><?= e($congregationName) ?></h3>
            <p><strong>Address:</strong> <?= e($congregationAddress) ?></p>
            <p><strong>Contact Number:</strong> <?= e($congregationContactNumber) ?></p>
            <?php if ($congregationFacebookPage): ?>
                <p><a href="<?= e($congregationFacebookPage) ?>" target="_blank" rel="noopener" aria-label="Facebook page"><i class="fa-brands fa-facebook"></i></a></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section panel">
    <div class="section-title">
        <h2>What is Messiahnic Believers?</h2>
        <span class="badge">Our faith</span>
    </div>

    <div class="grid cols-2">
        <div class="card">
            <h3>What this religion is about</h3>
            <p>
                Messiahnic believers are followers who honor the Almighty Father, keep the Scriptures with reverence,
                and seek to live in obedience to the Torah, the Prophets, and the teachings of Yahushua.
            </p>
            <p>
                This faith is not only about religion as a routine, but about a covenant life: repentance, holy living,
                mercy toward others, and faithful worship through prayer, study, and obedience.
            </p>
            <p>
                As a congregation, we encourage families, youth, and elders to walk together in truth, serve one another,
                and reflect the character and love of Yahushua in daily life.
            </p>
        </div>

        <div class="card">
            <h3>Why Yahushua is our true Savior</h3>
            <p>
                We believe Yahushua is the promised Messiah and the only way of salvation. Through his life,
                death, and resurrection, believers receive forgiveness, hope, and restored relationship through Yahushua.
            </p>
            <p>
                We believe his sacrifice fulfilled the promise of redemption, and his resurrection is the assurance of eternal life.
                Yahushua is our true Savior because he alone bore sin, conquered death, and leads his people in truth and righteousness.
            </p>
            <p class="badge">Sample verse</p>
            <p>
                <strong>Acts 4:12</strong> - "Salvation is found in no one else, for there is no other name under heaven
                given to mankind by which we must be saved."
            </p>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>