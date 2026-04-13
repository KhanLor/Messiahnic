<?php
require __DIR__ . '/bootstrap.php';
ensure_daily_ai_prayers();
ensure_yahushua_name_study_scriptures();
ensure_daily_ai_savior_scripture();

$pageTitle = 'Home';
$stats = [
    'believers' => 0,
    'teachings' => 0,
    'events' => 0,
    'prayers' => 0,
];
$latestTeachings = [];
$upcomingEvents = [];
$calendarEvents = [];
$hasUpcomingEvents = true;
$latestPrayers = [];
$dailyAiVerses = [];
$dailyVersesPerPage = 5;
$dailyVersePage = max(1, (int) ($_GET['verse_page'] ?? 1));
$totalDailyVerses = 0;
$totalDailyVersePages = 1;
$maxVisibleVerseLinks = 7;
$notifications = [];
$liveStreamUrl = LIVE_STREAM_URL;
$heroWorshipImage = app_url('assets/images/home/hero-worship.jpg');
$heroBibleImage = app_url('assets/images/home/hero-bible.jpg');
$fellowshipImage = app_url('assets/images/home/fellowship.jpg');
$studyImage = app_url('assets/images/home/study.jpg');

try {
    $stats['believers'] = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'believer' AND status = 'active'")->fetchColumn();
    $stats['teachings'] = (int) db()->query('SELECT COUNT(*) FROM teachings')->fetchColumn();
    $stats['events'] = (int) db()->query('SELECT COUNT(*) FROM events')->fetchColumn();
    $stats['prayers'] = (int) db()->query('SELECT COUNT(*) FROM prayer_requests pr JOIN users u ON u.id = pr.user_id WHERE pr.status = "approved" AND u.role = "admin"')->fetchColumn();

    $allEvents = db()->query('SELECT * FROM events ORDER BY date ASC, id DESC')->fetchAll();
    foreach ($allEvents as $event) {
        $day = $event['date'];
        if (!isset($calendarEvents[$day])) {
            $calendarEvents[$day] = [];
        }
        $calendarEvents[$day][] = [
            'title' => $event['title'],
            'type' => $event['type'],
            'description' => $event['description'],
        ];
    }

    $latestTeachings = db()->query('SELECT id, title, category, scripture_reference, created_at FROM teachings ORDER BY created_at DESC, id DESC LIMIT 3')->fetchAll();
    $upcomingEvents = db()->query('SELECT * FROM events WHERE date >= CURDATE() ORDER BY date ASC LIMIT 3')->fetchAll();
    if (!$upcomingEvents) {
        $hasUpcomingEvents = false;
        $upcomingEvents = db()->query('SELECT * FROM events ORDER BY date DESC, id DESC LIMIT 3')->fetchAll();
    }
    $latestPrayers = db()->query('SELECT pr.id, pr.message, pr.created_at, u.name FROM prayer_requests pr JOIN users u ON u.id = pr.user_id WHERE pr.status = "approved" AND u.role = "admin" ORDER BY pr.created_at DESC, pr.id DESC LIMIT 3')->fetchAll();
    $totalDailyVerses = (int) db()->query("SELECT COUNT(*) FROM scriptures WHERE content LIKE 'Daily Verse:%'")->fetchColumn();
    $totalDailyVersePages = max(1, (int) ceil($totalDailyVerses / $dailyVersesPerPage));
    if ($dailyVersePage > $totalDailyVersePages) {
        $dailyVersePage = $totalDailyVersePages;
    }

    $dailyVerseOffset = ($dailyVersePage - 1) * $dailyVersesPerPage;
    $dailyVerseStatement = db()->prepare("SELECT id, book, chapter, verse, content, testament_group FROM scriptures WHERE content LIKE 'Daily Verse:%' ORDER BY id DESC LIMIT ? OFFSET ?");
    $dailyVerseStatement->bindValue(1, $dailyVersesPerPage, PDO::PARAM_INT);
    $dailyVerseStatement->bindValue(2, $dailyVerseOffset, PDO::PARAM_INT);
    $dailyVerseStatement->execute();
    $dailyAiVerses = $dailyVerseStatement->fetchAll();
    $notifications = latest_notifications(4);
} catch (Throwable $exception) {
    flash('error', 'Database is not ready yet. Import schema.sql and configure your MySQL credentials.');
}

$believersDisplay = $stats['believers'] < 100 ? '100+' : number_format($stats['believers']) . '+';

$versePageBaseParams = $_GET;
unset($versePageBaseParams['verse_page']);
$buildVersePageUrl = static function (int $page) use ($versePageBaseParams): string {
    $params = $versePageBaseParams;
    if ($page > 1) {
        $params['verse_page'] = $page;
    }

    $query = http_build_query($params);
    $path = 'index.php' . ($query !== '' ? '?' . $query : '');

    return app_url($path) . '#daily-verses';
};

include __DIR__ . '/includes/header.php';
?>
<section class="hero home-hero" data-home-hero>
    <span class="home-hero-glow home-hero-glow-a" aria-hidden="true"></span>
    <span class="home-hero-glow home-hero-glow-b" aria-hidden="true"></span>

    <div class="hero-copy home-hero-copy">
        <span class="badge"><i class="fa-solid fa-scroll"></i> Yahushua-centered fellowship</span>
        <h1>Learn, gather, and grow together in the faith.</h1>
        <p>
            A modern Messiahnic community site for teachings, scripture study, feast days, prayer requests,
            and believer fellowship with role-based access for members and administrators.
        </p>
        <div class="actions">
            <a class="btn btn-primary" href="<?= e(app_url('teachings.php')) ?>">Browse teachings</a>
            <a class="btn btn-ghost" href="<?= e(app_url('prayer-requests.php')) ?>">Read daily prayers</a>
        </div>

        <div class="hero-stats home-hero-stats">
            <div class="stat home-stat"><strong class="home-stat-number" data-count-up data-count-target="<?= (int) $stats['believers'] ?>" data-count-suffix="+" data-count-min="100"><?= e($believersDisplay) ?></strong><span>Believers</span></div>
            <div class="stat home-stat"><strong class="home-stat-number" data-count-up data-count-target="<?= (int) $stats['teachings'] ?>"><?= e(number_format($stats['teachings'])) ?></strong><span>Teachings</span></div>
            <div class="stat home-stat"><strong class="home-stat-number" data-count-up data-count-target="<?= (int) $stats['events'] ?>"><?= e(number_format($stats['events'])) ?></strong><span>Events</span></div>
        </div>
    </div>

    <div class="hero-card home-hero-card" data-home-card>
        <div class="hero-gallery">
            <img src="<?= e($heroWorshipImage) ?>" alt="Believers gathered in worship">
            <img src="<?= e($heroBibleImage) ?>" alt="Open Bible and study materials">
        </div>
        <h3>Community snapshot</h3>
        <p class="muted">Recent activity, approved prayer requests, and upcoming gatherings.</p>
        <div class="list-stack">
            <?php foreach ($notifications as $notification): ?>
                <div class="stack-item">
                    <strong><?= e($notification['title']) ?></strong>
                    <div class="muted"><?= e($notification['type']) ?> · <?= e(format_date($notification['created_at'], 'M j, Y')) ?></div>
                    <p><?= e($notification['message']) ?></p>
                </div>
            <?php endforeach; ?>
            <?php if (!$notifications): ?>
                <p class="muted">Notifications will appear here when teachings or events are added.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section panel live-video-panel">
    <div class="section-title">
        <h2>Live Worship Video</h2>
        <span class="muted">Join the live stream fellowship</span>
    </div>
    <div class="live-video-frame">
        <iframe
            src="<?= e($liveStreamUrl) ?>"
            title="Live worship video stream"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
        ></iframe>
    </div>
</section>

<section class="section panel picture-strip">
    <div class="section-title">
        <h2>Faith Through Fellowship</h2>
        <span class="muted">Designed moments from the community life</span>
    </div>
    <div class="grid cols-2">
        <div class="picture-card">
            <img src="<?= e($fellowshipImage) ?>" alt="Community fellowship scene">
            <p class="muted">Gathering in unity and encouragement.</p>
        </div>
        <div class="picture-card">
            <img src="<?= e($studyImage) ?>" alt="Scripture learning scene">
            <p class="muted">Learning and growing through scripture together.</p>
        </div>
    </div>
</section>

<section class="section panel calendar-modern">
    <div class="section-title">
        <h2>Calendar At A Glance</h2>
        <span class="muted">See church events by date right from home</span>
    </div>

    <div class="calendar-controls">
        <button class="btn btn-ghost" type="button" id="homeCalendarPrev"><i class="fa-solid fa-chevron-left"></i> Previous</button>
        <strong id="homeCalendarMonthLabel">Month</strong>
        <button class="btn btn-ghost" type="button" id="homeCalendarNext">Next <i class="fa-solid fa-chevron-right"></i></button>
    </div>

    <div class="calendar-grid" id="homeEventsCalendar"></div>

    <div class="panel calendar-detail-panel" style="margin-top: 1rem;">
        <div class="section-title">
            <h3 style="margin: 0;">Selected day</h3>
            <span class="muted" id="homeSelectedDateLabel">No date selected</span>
        </div>
        <div id="homeSelectedDateEvents" class="list-stack">
            <p class="muted">Click a date with a marker to view details.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-title">
        <h2>Latest teachings</h2>
        <a class="muted" href="<?= e(app_url('teachings.php')) ?>">View all</a>
    </div>
    <div class="grid cols-3">
        <?php foreach ($latestTeachings as $teaching): ?>
            <article class="card">
                <div class="meta">
                    <span class="badge"><?= e($teaching['category']) ?></span>
                    <span><?= e($teaching['scripture_reference']) ?></span>
                </div>
                <h3><a href="<?= e(app_url('teaching.php?id=' . (int) $teaching['id'])) ?>"><?= e($teaching['title']) ?></a></h3>
                <p class="muted"><?= e(format_date($teaching['created_at'])) ?></p>
            </article>
        <?php endforeach; ?>
        <?php if (!$latestTeachings): ?>
            <div class="card">No teachings added yet.</div>
        <?php endif; ?>
    </div>
</section>

<section class="section grid cols-2">
    <div class="panel">
        <div class="section-title">
            <h2>Upcoming events</h2>
            <a class="muted" href="<?= e(app_url('events.php')) ?>">Calendar</a>
        </div>
        <?php if (!$hasUpcomingEvents && $upcomingEvents): ?>
            <p class="muted">No future events yet. Showing latest posted events.</p>
        <?php endif; ?>
        <div class="list-stack">
            <?php foreach ($upcomingEvents as $event): ?>
                <div class="stack-item">
                    <span class="badge"><?= e($event['type']) ?></span>
                    <h3><?= e($event['title']) ?></h3>
                    <p class="muted"><?= e(format_date($event['date'], 'M j, Y')) ?></p>
                    <p><?= e($event['description']) ?></p>
                </div>
            <?php endforeach; ?>
            <?php if (!$upcomingEvents): ?>
                <p class="muted">No upcoming events scheduled.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="section-title">
            <h2>Daily prayers</h2>
            <a class="muted" href="<?= e(app_url('prayer-requests.php')) ?>">All prayers</a>
        </div>
        <div class="list-stack">
            <?php foreach ($latestPrayers as $prayer): ?>
                <div class="stack-item">
                    <div class="meta">
                        <span><?= e(format_date($prayer['created_at'])) ?></span>
                    </div>
                    <p><?= e($prayer['message']) ?></p>
                </div>
            <?php endforeach; ?>
            <?php if (!$latestPrayers): ?>
                <p class="muted">Daily prayers will appear here automatically.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section panel" id="daily-verses">
    <div class="section-title">
        <h2>Daily verses</h2>
        <a class="muted" href="<?= e(app_url('scriptures.php')) ?>">Scripture library</a>
    </div>
    <div class="list-stack">
        <?php foreach ($dailyAiVerses as $verse): ?>
            <?php $verseContent = preg_replace('/^Daily Verse:\s*/', '', (string) $verse['content']) ?: (string) $verse['content']; ?>
            <article class="stack-item">
                <div class="meta">
                    <span class="badge"><?= e($verse['testament_group']) ?></span>
                    <span><?= e($verse['book']) ?> <?= e((string) $verse['chapter']) ?>:<?= e($verse['verse']) ?></span>
                </div>
                <p><?= e($verseContent) ?></p>
                <div class="actions">
                    <button class="btn btn-ghost verse-voice-btn" type="button" data-voice-text="<?= e($verseContent) ?>">Play Voice</button>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$dailyAiVerses): ?>
            <p class="muted">Daily verses will appear here automatically.</p>
        <?php endif; ?>
    </div>

    <?php if ($totalDailyVersePages > 1): ?>
        <?php
            $windowHalf = (int) floor($maxVisibleVerseLinks / 2);
            $startVersePage = max(1, $dailyVersePage - $windowHalf);
            $endVersePage = min($totalDailyVersePages, $startVersePage + $maxVisibleVerseLinks - 1);
            $startVersePage = max(1, $endVersePage - $maxVisibleVerseLinks + 1);
        ?>
        <nav class="pagination" aria-label="Daily verses pages">
            <?php if ($dailyVersePage > 1): ?>
                <a class="page-link" href="<?= e($buildVersePageUrl($dailyVersePage - 1)) ?>">Previous</a>
            <?php endif; ?>

            <?php if ($startVersePage > 1): ?>
                <a class="page-link" href="<?= e($buildVersePageUrl(1)) ?>">1</a>
                <?php if ($startVersePage > 2): ?>
                    <span class="page-link" aria-hidden="true">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startVersePage; $i <= $endVersePage; $i++): ?>
                <a class="page-link <?= $i === $dailyVersePage ? 'is-active' : '' ?>" href="<?= e($buildVersePageUrl($i)) ?>"><?= (int) $i ?></a>
            <?php endfor; ?>

            <?php if ($endVersePage < $totalDailyVersePages): ?>
                <?php if ($endVersePage < $totalDailyVersePages - 1): ?>
                    <span class="page-link" aria-hidden="true">...</span>
                <?php endif; ?>
                <a class="page-link" href="<?= e($buildVersePageUrl($totalDailyVersePages)) ?>"><?= (int) $totalDailyVersePages ?></a>
            <?php endif; ?>

            <?php if ($dailyVersePage < $totalDailyVersePages): ?>
                <a class="page-link" href="<?= e($buildVersePageUrl($dailyVersePage + 1)) ?>">Next</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<script>
    (() => {
        const voiceButtons = document.querySelectorAll('.verse-voice-btn');
        if (!voiceButtons.length || !('speechSynthesis' in window) || typeof SpeechSynthesisUtterance === 'undefined') {
            return;
        }

        let activeButton = null;

        function resetButton(button) {
            if (!button) {
                return;
            }
            button.textContent = 'Play Voice';
            button.dataset.playing = '0';
        }

        voiceButtons.forEach((button) => {
            button.dataset.playing = '0';
            button.addEventListener('click', () => {
                const isPlaying = button.dataset.playing === '1';
                if (isPlaying) {
                    window.speechSynthesis.cancel();
                    resetButton(button);
                    activeButton = null;
                    return;
                }

                if (activeButton && activeButton !== button) {
                    resetButton(activeButton);
                }

                window.speechSynthesis.cancel();

                const text = button.getAttribute('data-voice-text') || '';
                if (!text.trim()) {
                    return;
                }

                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'en-PH';
                utterance.rate = 0.9;
                utterance.onend = () => {
                    resetButton(button);
                    if (activeButton === button) {
                        activeButton = null;
                    }
                };
                utterance.onerror = () => {
                    resetButton(button);
                    if (activeButton === button) {
                        activeButton = null;
                    }
                };

                button.textContent = 'Stop Voice';
                button.dataset.playing = '1';
                activeButton = button;
                window.speechSynthesis.speak(utterance);
            });
        });
    })();

    (() => {
        const eventsByDate = <?= json_encode($calendarEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const calendarEl = document.getElementById('homeEventsCalendar');
        const monthLabelEl = document.getElementById('homeCalendarMonthLabel');
        const selectedDateLabelEl = document.getElementById('homeSelectedDateLabel');
        const selectedDateEventsEl = document.getElementById('homeSelectedDateEvents');
        const prevButton = document.getElementById('homeCalendarPrev');
        const nextButton = document.getElementById('homeCalendarNext');

        if (!calendarEl || !monthLabelEl || !selectedDateLabelEl || !selectedDateEventsEl || !prevButton || !nextButton) {
            return;
        }

        const today = new Date();
        let currentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        const weekdayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatDateKey(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function prettyDate(dateKey) {
            const parts = dateKey.split('-').map(Number);
            if (parts.length !== 3) return dateKey;
            return new Date(parts[0], parts[1] - 1, parts[2]).toLocaleDateString(undefined, {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            });
        }

        function renderSelectedDate(dateKey) {
            const entries = eventsByDate[dateKey] || [];
            selectedDateLabelEl.textContent = prettyDate(dateKey);

            if (entries.length === 0) {
                selectedDateEventsEl.innerHTML = '<p class="muted">No events for this date.</p>';
                return;
            }

            selectedDateEventsEl.innerHTML = entries
                .map((event) => `
                    <article class="stack-item calendar-detail-item">
                        <div class="meta"><span class="badge">${escapeHtml(event.type)}</span></div>
                        <strong>${escapeHtml(event.title)}</strong>
                        <p>${escapeHtml(event.description)}</p>
                    </article>
                `)
                .join('');
        }

        function renderCalendar() {
            const year = currentMonth.getFullYear();
            const month = currentMonth.getMonth();
            const firstDayOfMonth = new Date(year, month, 1);
            const startWeekday = firstDayOfMonth.getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            monthLabelEl.textContent = currentMonth.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
            calendarEl.innerHTML = '';

            weekdayNames.forEach((name) => {
                const head = document.createElement('div');
                head.className = 'calendar-head';
                head.textContent = name;
                calendarEl.appendChild(head);
            });

            for (let i = 0; i < startWeekday; i += 1) {
                const empty = document.createElement('div');
                empty.className = 'calendar-cell empty';
                calendarEl.appendChild(empty);
            }

            for (let day = 1; day <= daysInMonth; day += 1) {
                const date = new Date(year, month, day);
                const dateKey = formatDateKey(date);
                const hasEvents = Boolean(eventsByDate[dateKey] && eventsByDate[dateKey].length > 0);
                const isToday = formatDateKey(today) === dateKey;

                const cell = document.createElement('button');
                cell.type = 'button';
                cell.className = 'calendar-cell';
                if (hasEvents) cell.classList.add('has-event');
                if (isToday) cell.classList.add('today');

                cell.innerHTML = `
                    <span class="day-number">${day}</span>
                    ${hasEvents ? `<span class="event-dot">${eventsByDate[dateKey].length}</span>` : ''}
                `;

                cell.addEventListener('click', () => {
                    document.querySelectorAll('#homeEventsCalendar .calendar-cell.active').forEach((item) => item.classList.remove('active'));
                    cell.classList.add('active');
                    renderSelectedDate(dateKey);
                });

                calendarEl.appendChild(cell);
            }
        }

        prevButton.addEventListener('click', () => {
            currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1);
            renderCalendar();
        });

        nextButton.addEventListener('click', () => {
            currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1);
            renderCalendar();
        });

        renderCalendar();
        renderSelectedDate(formatDateKey(today));
    })();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
