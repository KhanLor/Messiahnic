<?php
require __DIR__ . '/../bootstrap.php';
require_admin();

$pageTitle = 'Manage Leadership';
$error = null;

$executiveMembersStored = get_setting('leadership_executive_members', '');
$decodedExecutiveMembers = json_decode($executiveMembersStored, true);

$normalizeExecutiveMembers = static function ($raw): array {
    $normalized = [];
    if (is_array($raw)) {
        foreach ($raw as $item) {
            if (is_array($item)) {
                $normalized[] = [
                    'name' => trim((string) ($item['name'] ?? '')),
                    'address' => trim((string) ($item['address'] ?? '')),
                    'contact_number' => trim((string) ($item['contact_number'] ?? '')),
                    'facebook_page' => trim((string) ($item['facebook_page'] ?? '')),
                    'picture' => trim((string) ($item['picture'] ?? '')),
                ];
                continue;
            }

            if (is_string($item)) {
                $name = trim($item);
                if ($name !== '') {
                    $normalized[] = [
                        'name' => $name,
                        'address' => '',
                        'contact_number' => '',
                        'facebook_page' => '',
                        'picture' => '',
                    ];
                }
            }
        }
    }

    return $normalized;
};

$previousExecutiveMembers = is_array($decodedExecutiveMembers)
    ? $normalizeExecutiveMembers($decodedExecutiveMembers)
    : $normalizeExecutiveMembers(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $executiveMembersStored ?: ''))));

if (is_post()) {
    try {
        verify_csrf();

        $leaderName = trim($_POST['leadership_name'] ?? '');
        $leaderAddress = trim($_POST['leadership_address'] ?? '');
        $leaderContactNumber = trim($_POST['leadership_contact_number'] ?? '');
        $leaderFacebookPage = trim($_POST['leadership_facebook_page'] ?? '');
        $memberNames = $_POST['executive_member_name'] ?? [];
        $memberAddresses = $_POST['executive_member_address'] ?? [];
        $memberContacts = $_POST['executive_member_contact_number'] ?? [];
        $memberFacebookPages = $_POST['executive_member_facebook_page'] ?? [];
        $memberExistingPictures = $_POST['executive_member_existing_picture'] ?? [];
        $memberRemovePictures = $_POST['remove_executive_member_picture'] ?? [];
        $memberFiles = $_FILES['executive_member_picture'] ?? null;

        if (!is_array($memberNames)) {
            $memberNames = [];
        }
        if (!is_array($memberContacts)) {
            $memberContacts = [];
        }
        if (!is_array($memberAddresses)) {
            $memberAddresses = [];
        }
        if (!is_array($memberFacebookPages)) {
            $memberFacebookPages = [];
        }
        if (!is_array($memberExistingPictures)) {
            $memberExistingPictures = [];
        }
        if (!is_array($memberRemovePictures)) {
            $memberRemovePictures = [];
        }

        $executiveMembers = [];
        $totalMembers = max(count($memberNames), count($memberAddresses), count($memberContacts), count($memberFacebookPages), count($memberExistingPictures));
        for ($index = 0; $index < $totalMembers; $index++) {
            $memberName = trim((string) ($memberNames[$index] ?? ''));
            $memberAddress = trim((string) ($memberAddresses[$index] ?? ''));
            $memberContactNumber = trim((string) ($memberContacts[$index] ?? ''));
            $memberFacebookPage = trim((string) ($memberFacebookPages[$index] ?? ''));
            $memberPicture = trim((string) ($memberExistingPictures[$index] ?? ''));

            if (isset($memberRemovePictures[$index]) && (string) $memberRemovePictures[$index] === '1') {
                if ($memberPicture && file_exists(__DIR__ . '/../' . $memberPicture)) {
                    @unlink(__DIR__ . '/../' . $memberPicture);
                }
                $memberPicture = '';
            }

            if (is_array($memberFiles)
                && isset($memberFiles['error'][$index])
                && (int) $memberFiles['error'][$index] !== UPLOAD_ERR_NO_FILE
            ) {
                $singleMemberFile = [
                    'name' => $memberFiles['name'][$index] ?? '',
                    'type' => $memberFiles['type'][$index] ?? '',
                    'tmp_name' => $memberFiles['tmp_name'][$index] ?? '',
                    'error' => $memberFiles['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $memberFiles['size'][$index] ?? 0,
                ];

                $newMemberPicture = upload_file($singleMemberFile, 'leadership', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                if ($newMemberPicture) {
                    if ($memberPicture && file_exists(__DIR__ . '/../' . $memberPicture)) {
                        @unlink(__DIR__ . '/../' . $memberPicture);
                    }
                    $memberPicture = $newMemberPicture;
                }
            }

            if ($memberName === '' && $memberAddress === '' && $memberContactNumber === '' && $memberFacebookPage === '' && $memberPicture === '') {
                continue;
            }

            if ($memberName === '') {
                throw new RuntimeException('Executive member name is required when details are provided.');
            }

            $executiveMembers[] = [
                'name' => $memberName,
                'address' => $memberAddress,
                'contact_number' => $memberContactNumber,
                'facebook_page' => $memberFacebookPage,
                'picture' => $memberPicture,
            ];
        }

        if ($leaderName === '' || $leaderAddress === '' || $leaderContactNumber === '') {
            throw new RuntimeException('Name, address, and contact number are required.');
        }

        $leaderPicturePath = get_setting('leadership_picture', '');
        if (!empty($_FILES['leadership_picture']['name'])) {
            $newLeaderPicturePath = upload_file($_FILES['leadership_picture'], 'leadership', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            if ($newLeaderPicturePath) {
                if ($leaderPicturePath && file_exists(__DIR__ . '/../' . $leaderPicturePath)) {
                    @unlink(__DIR__ . '/../' . $leaderPicturePath);
                }
                $leaderPicturePath = $newLeaderPicturePath;
            }
        }

        if (isset($_POST['remove_leadership_picture']) && $_POST['remove_leadership_picture'] === '1') {
            if ($leaderPicturePath && file_exists(__DIR__ . '/../' . $leaderPicturePath)) {
                @unlink(__DIR__ . '/../' . $leaderPicturePath);
            }
            $leaderPicturePath = '';
        }

        set_setting('leadership_name', $leaderName);
        set_setting('leadership_address', $leaderAddress);
        set_setting('leadership_contact_number', $leaderContactNumber);
        set_setting('leadership_facebook_page', $leaderFacebookPage);
        $executiveMembersJson = json_encode($executiveMembers);
        if (!is_string($executiveMembersJson)) {
            throw new RuntimeException('Unable to save executive members.');
        }
        set_setting('leadership_executive_members', $executiveMembersJson);
        set_setting('leadership_picture', $leaderPicturePath);

        $savedPictures = array_values(array_filter(array_map(static fn ($member): string => (string) ($member['picture'] ?? ''), $executiveMembers)));
        foreach ($previousExecutiveMembers as $previousMember) {
            $previousPicture = (string) ($previousMember['picture'] ?? '');
            if ($previousPicture === '') {
                continue;
            }
            if (!in_array($previousPicture, $savedPictures, true) && file_exists(__DIR__ . '/../' . $previousPicture)) {
                @unlink(__DIR__ . '/../' . $previousPicture);
            }
        }

        flash('success', 'Leadership page updated.');
        redirect('admin/leadership.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$leaderNameValue = get_setting('leadership_name', '');
$leaderAddressValue = get_setting('leadership_address', '');
$leaderContactNumberValue = get_setting('leadership_contact_number', '');
$leaderFacebookPageValue = get_setting('leadership_facebook_page', '');
$executiveMembersValue = $previousExecutiveMembers;
if (!$executiveMembersValue) {
    $executiveMembersValue = [[
        'name' => '',
        'address' => '',
        'contact_number' => '',
        'facebook_page' => '',
        'picture' => '',
    ]];
}
$leaderPictureValue = get_setting('leadership_picture', '');

include __DIR__ . '/../includes/header.php';
?>
<section class="panel">
    <div class="section-title">
        <h2>Manage leadership</h2>
        <span class="muted">Update the congregation leader details</span>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="form" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label>
            Name
            <input type="text" name="leadership_name" value="<?= e($leaderNameValue) ?>" required>
        </label>
        <label>
            Address
            <input type="text" name="leadership_address" value="<?= e($leaderAddressValue) ?>" required>
        </label>
        <label>
            Contact Number
            <input type="tel" name="leadership_contact_number" value="<?= e($leaderContactNumberValue) ?>" required>
        </label>
        <label>
            Facebook Page
            <input type="url" name="leadership_facebook_page" value="<?= e($leaderFacebookPageValue) ?>" placeholder="https://facebook.com/yourpage">
        </label>
        <label>Executive Members</label>
        <div id="executive-members-list" style="display: grid; gap: 0.75rem; margin-bottom: 0.75rem;">
            <?php foreach ($executiveMembersValue as $index => $member): ?>
                <div class="executive-member-row" style="border: 1px solid var(--line); border-radius: 12px; padding: 0.75rem; display: grid; gap: 0.75rem;">
                    <input type="hidden" name="executive_member_existing_picture[]" value="<?= e((string) ($member['picture'] ?? '')) ?>">
                    <label>
                        Name
                        <input type="text" name="executive_member_name[]" value="<?= e((string) ($member['name'] ?? '')) ?>" placeholder="Executive member name">
                    </label>
                    <label>
                        Address
                        <input type="text" name="executive_member_address[]" value="<?= e((string) ($member['address'] ?? '')) ?>" placeholder="Member address">
                    </label>
                    <label>
                        Contact Number
                        <input type="tel" name="executive_member_contact_number[]" value="<?= e((string) ($member['contact_number'] ?? '')) ?>" placeholder="09xxxxxxxxx">
                    </label>
                    <label>
                        Facebook Page
                        <input type="url" name="executive_member_facebook_page[]" value="<?= e((string) ($member['facebook_page'] ?? '')) ?>" placeholder="https://facebook.com/yourpage">
                    </label>
                    <label>
                        Picture
                        <input type="file" name="executive_member_picture[]" accept="image/*">
                    </label>
                    <?php if (!empty($member['picture'])): ?>
                        <div>
                            <img src="<?= e(app_url((string) $member['picture'])) ?>" alt="Executive member picture" style="max-width: 140px; border-radius: 8px;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                                <input type="checkbox" name="remove_executive_member_picture[<?= (int) $index ?>]" value="1">
                                Remove picture
                            </label>
                        </div>
                    <?php endif; ?>
                    <button class="btn btn-ghost" type="button" data-remove-member>Remove Member</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary" type="button" id="addExecutiveMember">Add Executive Member</button>
        <label>
            Leader Picture
            <input type="file" name="leadership_picture" accept="image/*">
        </label>
        <?php if ($leaderPictureValue): ?>
            <div style="margin: 1rem 0;">
                <img src="<?= e(app_url($leaderPictureValue)) ?>" alt="Leader picture" style="max-width: 200px; border-radius: 8px;"/>
                <label style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="remove_leadership_picture" value="1">
                    Remove current picture
                </label>
            </div>
        <?php endif; ?>
        <div class="actions">
            <button class="btn btn-primary" type="submit">Save Leadership</button>
            <a class="btn btn-ghost" href="<?= e(app_url('leadership.php')) ?>" target="_blank" rel="noopener">View public page</a>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('executive-members-list');
    const addButton = document.getElementById('addExecutiveMember');

    if (!list || !addButton) {
        return;
    }

    function bindRemoveButtons() {
        const buttons = list.querySelectorAll('[data-remove-member]');
        buttons.forEach(function (button) {
            button.onclick = function () {
                const row = button.closest('.executive-member-row');
                if (!row) {
                    return;
                }
                row.remove();
            };
        });
    }

    addButton.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'executive-member-row';
        row.style.border = '1px solid var(--line)';
        row.style.borderRadius = '12px';
        row.style.padding = '0.75rem';
        row.style.display = 'grid';
        row.style.gap = '0.75rem';
        row.innerHTML =
            '<input type="hidden" name="executive_member_existing_picture[]" value="">' +
            '<label>Name<input type="text" name="executive_member_name[]" value="" placeholder="Executive member name"></label>' +
            '<label>Address<input type="text" name="executive_member_address[]" value="" placeholder="Member address"></label>' +
            '<label>Contact Number<input type="tel" name="executive_member_contact_number[]" value="" placeholder="09xxxxxxxxx"></label>' +
            '<label>Facebook Page<input type="url" name="executive_member_facebook_page[]" value="" placeholder="https://facebook.com/yourpage"></label>' +
            '<label>Picture<input type="file" name="executive_member_picture[]" accept="image/*"></label>' +
            '<button class="btn btn-ghost" type="button" data-remove-member>Remove Member</button>';
        list.appendChild(row);
        bindRemoveButtons();
    });

    bindRemoveButtons();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>