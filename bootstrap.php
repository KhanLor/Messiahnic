<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('APP_NAME', 'Messiahnic Believer');

define('DB_HOST', getenv('MESSIAH_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('MESSIAH_DB_NAME') ?: 'messiahnic');
define('DB_USER', getenv('MESSIAH_DB_USER') ?: 'root');
define('DB_PASS', getenv('MESSIAH_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
define('GOOGLE_MAPS_API_KEY', getenv('MESSIAH_GOOGLE_MAPS_API_KEY') ?: 'AIzaSyC9JqK9iwTSABGGdxNFOSD15OXH_alO9O8');
define('LIVE_STREAM_URL', getenv('MESSIAH_LIVE_STREAM_URL') ?: 'https://www.youtube.com/embed/DE8FRQoWZK0');

define('UPLOAD_ROOT', __DIR__ . DIRECTORY_SEPARATOR . 'uploads');

if (!is_dir(UPLOAD_ROOT)) {
    mkdir(UPLOAD_ROOT, 0775, true);
}

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    $pdo = null;
    $dbError = $exception->getMessage();
}

function db(): PDO
{
    global $pdo, $dbError;

    if (!$pdo instanceof PDO) {
        throw new RuntimeException(
            'Database connection is not ready. Create the database and update bootstrap.php credentials.' .
            (!empty($dbError) ? ' Error: ' . $dbError : '')
        );
    }

    return $pdo;
}

function app_base(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = str_replace('\\', '/', dirname($scriptName));

    while ($base !== '' && $base !== '/' && in_array(basename($base), ['auth', 'admin', 'includes', 'assets'], true)) {
        $base = str_replace('\\', '/', dirname($base));
    }

    return $base === '/' ? '' : rtrim($base, '/');
}

function app_url(string $path = ''): string
{
    $base = app_base();
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if ($submitted === '' || !hash_equals($expected, $submitted)) {
        throw new RuntimeException('Invalid form submission.');
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $value;
}

function old(string $key, string $default = ''): string
{
    return $_SESSION['old'][$key] ?? $default;
}

function set_old(): void
{
    $_SESSION['old'] = $_POST;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function current_user(): ?array
{
    static $cachedUser = null;

    if ($cachedUser !== null) {
        return $cachedUser;
    }

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $statement = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $statement->execute([$_SESSION['user_id']]);
    $user = $statement->fetch() ?: null;

    if ($user && ($user['status'] ?? 'active') === 'active') {
        $cachedUser = $user;
        return $cachedUser;
    }

    unset($_SESSION['user_id']);

    return null;
}

function auth_check(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!auth_check()) {
        flash('error', 'Please log in to continue.');
        redirect('auth/login.php');
    }
}

function require_admin(): void
{
    $user = current_user();

    if (!$user || ($user['role'] ?? 'believer') !== 'admin') {
        http_response_code(403);
        exit('Forbidden');
    }
}

function ensure_upload_dir(string $folder): string
{
    $target = rtrim(UPLOAD_ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($folder, DIRECTORY_SEPARATOR);

    if (!is_dir($target)) {
        mkdir($target, 0775, true);
    }

    return $target;
}

function ensure_church_locations_table(): void
{
    db()->exec(<<<SQL
CREATE TABLE IF NOT EXISTS church_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(120) NOT NULL,
    pastor_name VARCHAR(180) NULL,
    photo_path VARCHAR(255) NULL,
    latitude DECIMAL(10, 7) NOT NULL,
    longitude DECIMAL(10, 7) NOT NULL,
    contact VARCHAR(120) NULL,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_church_locations_name (name),
    INDEX idx_church_locations_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    try {
        db()->exec('ALTER TABLE church_locations ADD COLUMN pastor_name VARCHAR(180) NULL AFTER city');
    } catch (Throwable) {
        // Ignore if the column already exists.
    }

    try {
        db()->exec('ALTER TABLE church_locations ADD COLUMN photo_path VARCHAR(255) NULL AFTER pastor_name');
    } catch (Throwable) {
        // Ignore if the column already exists.
    }
}

function ensure_site_settings_table(): void
{
    db()->exec(<<<SQL
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);
}

function ensure_comments_table(): void
{
    db()->exec(<<<SQL
CREATE TABLE IF NOT EXISTS comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    teaching_id INT UNSIGNED NOT NULL,
    guest_name VARCHAR(150) NULL,
    guest_email VARCHAR(190) NULL,
    comment TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_comments_teaching FOREIGN KEY (teaching_id) REFERENCES teachings(id) ON DELETE CASCADE,
    INDEX idx_comments_teaching (teaching_id),
    INDEX idx_comments_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL);

    try {
        db()->exec('ALTER TABLE comments MODIFY user_id INT UNSIGNED NULL');
    } catch (Throwable) {
        // Ignore if already nullable.
    }

    try {
        db()->exec('ALTER TABLE comments ADD COLUMN guest_name VARCHAR(150) NULL AFTER teaching_id');
    } catch (Throwable) {
        // Ignore if the column already exists.
    }

    try {
        db()->exec('ALTER TABLE comments ADD COLUMN guest_email VARCHAR(190) NULL AFTER guest_name');
    } catch (Throwable) {
        // Ignore if the column already exists.
    }

    try {
        // Existing rows from older builds should stay visible, so migrate them as approved first.
        db()->exec('ALTER TABLE comments ADD COLUMN status ENUM("pending", "approved", "rejected") NOT NULL DEFAULT "approved" AFTER comment');
    } catch (Throwable) {
        // Ignore if the column already exists.
    }

    try {
        db()->exec('ALTER TABLE comments MODIFY status ENUM("pending", "approved", "rejected") NOT NULL DEFAULT "pending"');
    } catch (Throwable) {
        // Ignore if status cannot be modified.
    }

    try {
        db()->exec('ALTER TABLE comments DROP FOREIGN KEY fk_comments_user');
    } catch (Throwable) {
        // Ignore if the foreign key is absent or has already been changed.
    }

    try {
        db()->exec('ALTER TABLE comments ADD CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
    } catch (Throwable) {
        // Ignore if the foreign key already exists with the desired behavior.
    }

    try {
        db()->exec('ALTER TABLE comments ADD INDEX idx_comments_status (status)');
    } catch (Throwable) {
        // Ignore if the index already exists.
    }

}

function get_setting(string $key, string $default = ''): string
{
    ensure_site_settings_table();

    $statement = db()->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1');
    $statement->execute([$key]);
    $value = $statement->fetchColumn();

    if ($value === false || $value === null) {
        return $default;
    }

    return (string) $value;
}

function set_setting(string $key, string $value): void
{
    ensure_site_settings_table();

    $statement = db()->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $statement->execute([$key, $value]);
}

function facebook_live_embed_url(?string $rawUrl): ?string
{
    $url = trim((string) $rawUrl);
    if ($url === '') {
        return null;
    }

    if (!preg_match('/^https?:\/\//i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    if (stripos($url, 'facebook.com/plugins/video.php') !== false) {
        return $url;
    }

    if (stripos($url, 'facebook.com') === false && stripos($url, 'fb.watch') === false) {
        return null;
    }

    return 'https://www.facebook.com/plugins/video.php?href=' . rawurlencode($url) . '&show_text=false&autoplay=true';
}

function philippines_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
}

function philippines_today_label(): string
{
    return philippines_now()->format('F j, Y');
}

function prayer_slot_for_hour(int $hour): string
{
    if ($hour >= 8 && $hour < 12) {
        return 'Morning';
    }

    if ($hour >= 12 && $hour < 18) {
        return 'Afternoon';
    }

    return 'Evening';
}

function ensure_daily_ai_prayers(): void
{
    $adminStatement = db()->query('SELECT id FROM users WHERE role = "admin" AND status = "active" ORDER BY id ASC LIMIT 1');
    $adminId = (int) ($adminStatement->fetchColumn() ?: 0);

    if ($adminId <= 0) {
        return;
    }

    $todayLabel = philippines_today_label();
    $todaySqlDate = philippines_now()->format('Y-m-d');
    $slots = [
        'Morning' => [
            'Almighty Father, as this morning begins, we bow before you with thankful hearts. Fill our minds with your wisdom, direct our steps in righteousness, and guard us from pride and fear. Let your holy peace cover our homes, our work, and our fellowship. Teach us to honor your Name in every word and action, and make us a blessing to everyone we meet today.',
            'Almighty Father, we praise you for the breath of life this morning. Cleanse our hearts, renew our strength, and awaken in us a deeper love for your truth. Help us walk humbly, forgive quickly, and serve joyfully. Let your light lead us through every decision, and keep our families protected under your mercy and covenant love throughout this day.',
            'Almighty Father, we dedicate this new morning to you. Establish our thoughts in holiness, align our plans with your will, and give us courage to obey your word. Pour grace over our households, restore hope where there is heaviness, and let your joy rise in us. May this day reflect your goodness, your compassion, and your unchanging faithfulness.',
        ],
        'Afternoon' => [
            'Almighty Father, in this afternoon hour renew our strength and steady our hearts. When we feel tired, remind us that your grace is sufficient. Keep our speech gentle, our minds clear, and our hands faithful in every task. Let your wisdom guide our choices, your peace calm our anxieties, and your love shape our relationships so we can honor you in all we do.',
            'Almighty Father, as the day continues, refresh us by your Spirit. Remove distraction and discouragement, and fill us with patient endurance. Teach us to act with integrity, speak with kindness, and serve with compassion. Cover our families, coworkers, and community with your mercy, and let your truth remain the lamp that guides us in this afternoon season.',
            'Almighty Father, we lift our hearts to you this afternoon. Strengthen us in the middle of our responsibilities and help us remain faithful in small and great things. Give us clarity for every decision and humility in every conversation. May your presence rest on us, your favor surround us, and your righteousness be seen through our attitudes and actions.',
        ],
        'Evening' => [
            'Almighty Father, as evening comes we thank you for carrying us through this day. Forgive our shortcomings, cleanse our hearts, and restore our joy in your presence. Let your peace settle over our homes, protect our families as we rest, and quiet every burden in our minds. Fill this night with your comfort and prepare us to rise tomorrow with faith and renewed hope.',
            'Almighty Father, we close this day in gratitude. Thank you for your mercy, provision, and guidance from morning until now. Wash away fear, disappointment, and fatigue, and replace them with calm trust in you. Surround every household with your protection, heal those who are weary, and grant us restful sleep under the covering of your faithful love.',
            'Almighty Father, we bless your Name in this evening hour. You have sustained us, corrected us, and shown us your kindness. We surrender every concern into your hands and ask for your peace to guard our hearts. Keep watch over our loved ones, renew our spirits while we sleep, and awaken us tomorrow to walk again in your truth and purpose.',
        ],
    ];

    $existsStatement = db()->prepare(
        'SELECT COUNT(*) FROM prayer_requests WHERE user_id = ? AND status = "approved" AND DATE(created_at) = ? AND message LIKE ?'
    );
    $insertStatement = db()->prepare('INSERT INTO prayer_requests (user_id, message, status, created_at) VALUES (?, ?, "approved", ?)');
    $todayRowsStatement = db()->prepare(
        'SELECT id, message FROM prayer_requests WHERE user_id = ? AND status = "approved" AND DATE(created_at) = ? AND (message LIKE "Morning Prayer:%" OR message LIKE "Afternoon Prayer:%" OR message LIKE "Evening Prayer:%") ORDER BY id DESC'
    );
    $updateMessageStatement = db()->prepare('UPDATE prayer_requests SET message = ? WHERE id = ?');

    // Normalize older generated text for today by removing trailing date suffix from prayer messages.
    $todayRowsStatement->execute([$adminId, $todaySqlDate]);
    foreach ($todayRowsStatement->fetchAll() as $row) {
        $message = (string) ($row['message'] ?? '');
        $cleaned = preg_replace('/\s*\([^\)]*\)\s*$/', '', $message) ?: $message;
        $cleaned = str_replace('Almighty God', 'Almighty Father', $cleaned);
        if ($cleaned !== $message) {
            $updateMessageStatement->execute([$cleaned, (int) $row['id']]);
        }
    }

    foreach ($slots as $slot => $options) {
        $existsPattern = $slot . ' Prayer:%';
        $existsStatement->execute([$adminId, $todaySqlDate, $existsPattern]);
        if ((int) $existsStatement->fetchColumn() > 0) {
            continue;
        }

        $index = (int) (abs(crc32($todayLabel . '|' . $slot)) % count($options));
        $message = $slot . ' Prayer: ' . $options[$index];
        $createdAt = philippines_now()->format('Y-m-d H:i:s');
        $insertStatement->execute([$adminId, $message, $createdAt]);
    }
}

function daily_admin_prayers_by_slot(): array
{
    $slots = ['Morning', 'Afternoon', 'Evening'];
    $todayLabel = philippines_today_label();
    $result = [
        'Morning' => null,
        'Afternoon' => null,
        'Evening' => null,
    ];

    $statement = db()->query(
        'SELECT pr.message, pr.created_at
         FROM prayer_requests pr
         JOIN users u ON u.id = pr.user_id
         WHERE pr.status = "approved"
           AND u.role = "admin"
           AND (pr.message LIKE "Morning Prayer:%" OR pr.message LIKE "Afternoon Prayer:%" OR pr.message LIKE "Evening Prayer:%")
         ORDER BY pr.created_at DESC, pr.id DESC
         LIMIT 90'
    );
    $rows = $statement->fetchAll();

    foreach ($slots as $slot) {
        foreach ($rows as $row) {
            $message = (string) ($row['message'] ?? '');
            if (strpos($message, $slot . ' Prayer:') !== 0) {
                continue;
            }

            if ($result[$slot] === null) {
                $result[$slot] = $message;
            }
        }
    }

    return $result;
}

function ensure_yahushua_name_study_scriptures(): void
{
    $studyPack = [
        ['book' => 'Matthew', 'chapter' => 1, 'verse' => '21', 'content' => 'The child is given the name Yahushua, for he will save his people from their sins.', 'group' => 'Gospel'],
        ['book' => 'Luke', 'chapter' => 1, 'verse' => '31', 'content' => 'The messenger announces a son and says his name will be Yahushua.', 'group' => 'Gospel'],
        ['book' => 'Acts', 'chapter' => 4, 'verse' => '12', 'content' => 'Salvation is found in no other name given among people by which we must be saved.', 'group' => 'Gospel'],
        ['book' => 'Matthew', 'chapter' => 1, 'verse' => '23', 'content' => 'Immanuel means "Almighty Father with us," showing the promised presence of the Most High through the Messiah.', 'group' => 'Gospel'],
        ['book' => 'Acts', 'chapter' => 26, 'verse' => '14', 'content' => 'Shaul hears the risen Messiah call him in Hebrew, showing personal authority and covenant identity.', 'group' => 'Gospel'],
        ['book' => 'John', 'chapter' => 19, 'verse' => '19-20', 'content' => 'Pilate\'s inscription over the cross identifies the Nazarene and is written in multiple languages for all to read.', 'group' => 'Gospel'],
        ['book' => 'Exodus', 'chapter' => 23, 'verse' => '21', 'content' => 'The people are told to obey the One sent with the Name, because rebellion against him is serious.', 'group' => 'Torah'],
        ['book' => 'Psalm', 'chapter' => 68, 'verse' => '4', 'content' => 'Sing to Elohim and honor his Name with joy and praise.', 'group' => 'Writings'],
        ['book' => 'Joshua', 'chapter' => 1, 'verse' => '1', 'content' => 'After Moses, Joshua (Yahushua form in Hebrew tradition) is called to lead in covenant obedience.', 'group' => 'Prophets'],
        ['book' => 'Philippians', 'chapter' => 2, 'verse' => '9-11', 'content' => 'The Most High exalts Messiah and every knee bows to the Name above all names.', 'group' => 'Gospel'],
    ];

    $existsStatement = db()->prepare('SELECT id FROM scriptures WHERE book = ? AND chapter = ? AND verse = ? AND content = ? LIMIT 1');
    $insertStatement = db()->prepare('INSERT INTO scriptures (book, chapter, verse, content, testament_group, is_highlighted) VALUES (?, ?, ?, ?, ?, 1)');

    foreach ($studyPack as $item) {
        $existsStatement->execute([$item['book'], $item['chapter'], $item['verse'], $item['content']]);
        if ($existsStatement->fetchColumn()) {
            continue;
        }

        $insertStatement->execute([
            $item['book'],
            $item['chapter'],
            $item['verse'],
            $item['content'],
            $item['group'],
        ]);
    }
}

function ensure_daily_ai_savior_scripture(): void
{
    ensure_site_settings_table();

    // Normalize older rows to keep Yahushua wording consistent.
    db()->exec('UPDATE scriptures SET content = REPLACE(content, "Jesus", "Yahushua")');
    db()->exec('UPDATE scriptures SET content = REPLACE(content, "God", "Almighty Father")');

    $todaySqlDate = philippines_now()->format('Y-m-d');
    $lastGeneratedDate = get_setting('daily_ai_scripture_date', '');
    $quotaDate = get_setting('daily_ai_scripture_quota_date', '');
    $quotaCount = (int) get_setting('daily_ai_scripture_quota_count', '0');

    if ($quotaDate !== $todaySqlDate) {
        $quotaCount = 0;
    }

    $dailyTarget = 10;
    if ($lastGeneratedDate === $todaySqlDate && $quotaCount >= $dailyTarget) {
        return;
    }

    $dailyPack = [
        [
            'book' => 'Acts',
            'chapter' => 4,
            'verse' => '12',
            'content' => 'AI Daily Verse: Yahushua is our true Savior, and salvation is found in no other name under heaven for mankind.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'John',
            'chapter' => 14,
            'verse' => '6',
            'content' => 'AI Daily Verse: Yahushua declares the way, the truth, and the life, revealing that rescue and life come through him.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'Matthew',
            'chapter' => 1,
            'verse' => '21',
            'content' => 'AI Daily Verse: Yahushua saves his people from sin, showing his mission as the true Savior promised from the beginning.',
            'group' => 'Gospel',
        ],
        [
            'book' => '1 John',
            'chapter' => 4,
            'verse' => '14',
            'content' => 'AI Daily Verse: The testimony affirms Yahushua as Savior, sent to bring life, peace, and reconciliation.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'Romans',
            'chapter' => 10,
            'verse' => '9',
            'content' => 'AI Daily Verse: Trusting and confessing Yahushua brings salvation and establishes faithful obedience.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'Philippians',
            'chapter' => 2,
            'verse' => '9-11',
            'content' => 'AI Daily Verse: Yahushua is exalted above all names, and every knee will bow in reverence to his authority.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'Hebrews',
            'chapter' => 7,
            'verse' => '25',
            'content' => 'AI Daily Verse: Yahushua saves completely those who come near through him, and his intercession remains faithful.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'John',
            'chapter' => 3,
            'verse' => '16-17',
            'content' => 'AI Daily Verse: Through Yahushua, the world is offered life and deliverance, revealing the mercy and purpose of the Almighty Father.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'Luke',
            'chapter' => 19,
            'verse' => '10',
            'content' => 'AI Daily Verse: Yahushua came to seek and save the lost, showing his mission as true Savior.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'Titus',
            'chapter' => 2,
            'verse' => '13',
            'content' => 'AI Daily Verse: Believers wait in hope for Yahushua, whose appearing confirms salvation and faithful expectation.',
            'group' => 'Gospel',
        ],
        [
            'book' => '2 Timothy',
            'chapter' => 1,
            'verse' => '10',
            'content' => 'AI Daily Verse: Yahushua brings life and immortality to light, revealing the power of salvation.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'Isaiah',
            'chapter' => 53,
            'verse' => '5',
            'content' => 'AI Daily Verse: The suffering servant prophecy points to Yahushua, through whom healing, peace, and restoration are given.',
            'group' => 'Prophets',
        ],
        [
            'book' => 'John',
            'chapter' => 11,
            'verse' => '25',
            'content' => 'AI Daily Verse: Yahushua is the resurrection and the life, assuring victory over death to those who trust him.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'Acts',
            'chapter' => 13,
            'verse' => '38-39',
            'content' => 'AI Daily Verse: Through Yahushua, forgiveness and justification are proclaimed to all who believe.',
            'group' => 'Gospel',
        ],
        [
            'book' => '1 Peter',
            'chapter' => 1,
            'verse' => '3',
            'content' => 'AI Daily Verse: Through Yahushua, living hope rises by resurrection power and covenant mercy.',
            'group' => 'Gospel',
        ],
        [
            'book' => 'Revelation',
            'chapter' => 1,
            'verse' => '5',
            'content' => 'AI Daily Verse: Yahushua, faithful witness and ruler, loves and frees believers from sin by his sacrifice.',
            'group' => 'Gospel',
        ],
    ];

    $startIndex = (int) (abs(crc32($todaySqlDate)) % count($dailyPack));
    $remainingCount = max(0, $dailyTarget - $quotaCount);
    $dailyCount = min($remainingCount, count($dailyPack));

    if ($dailyCount <= 0) {
        set_setting('daily_ai_scripture_date', $todaySqlDate);
        set_setting('daily_ai_scripture_quota_date', $todaySqlDate);
        set_setting('daily_ai_scripture_quota_count', (string) $quotaCount);
        return;
    }

    $insertStatement = db()->prepare('INSERT INTO scriptures (book, chapter, verse, content, testament_group, is_highlighted) VALUES (?, ?, ?, ?, ?, 1)');
    for ($offset = 0; $offset < $dailyCount; $offset++) {
        $item = $dailyPack[($startIndex + $quotaCount + $offset) % count($dailyPack)];
        $insertStatement->execute([
            $item['book'],
            $item['chapter'],
            $item['verse'],
            $item['content'],
            $item['group'],
        ]);
    }

    set_setting('daily_ai_scripture_date', $todaySqlDate);
    set_setting('daily_ai_scripture_quota_date', $todaySqlDate);
    set_setting('daily_ai_scripture_quota_count', (string) ($quotaCount + $dailyCount));
}

function upload_file(array $file, string $folder, array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'pdf']): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Unsupported file type.');
    }

    $targetDir = ensure_upload_dir($folder);
    $filename = bin2hex(random_bytes(12)) . '.' . $extension;
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Unable to save uploaded file.');
    }

    return 'uploads/' . trim($folder, '/') . '/' . $filename;
}

function create_notification(string $type, string $title, string $message, ?string $link = null): void
{
    $statement = db()->prepare('INSERT INTO notifications (type, title, message, link, created_at) VALUES (?, ?, ?, ?, NOW())');
    $statement->execute([$type, $title, $message, $link]);
}

function seed_notification_from_action(string $title, string $type, string $message, ?string $link = null): void
{
    try {
        create_notification($type, $title, $message, $link);
    } catch (Throwable) {
        // Ignore notification errors so the main action still succeeds.
    }
}

function latest_notifications(int $limit = 5): array
{
    $statement = db()->prepare('SELECT * FROM notifications ORDER BY created_at DESC, id DESC LIMIT ?');
    $statement->bindValue(1, $limit, PDO::PARAM_INT);
    $statement->execute();

    return $statement->fetchAll();
}

function format_date(?string $value, string $format = 'M j, Y'): string
{
    if (!$value) {
        return 'N/A';
    }

    try {
        return (new DateTimeImmutable($value))->format($format);
    } catch (Throwable) {
        return $value;
    }
}

function nav_is(string $file): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '') === $file ? 'active' : '';
}

function render_flash_messages(): void
{
    foreach (['success', 'error', 'info'] as $type) {
        $message = flash($type);
        if ($message) {
            echo '<div class="alert alert-' . e($type) . '">' . e($message) . '</div>';
        }
    }
}
