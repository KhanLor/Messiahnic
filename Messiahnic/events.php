<?php
require __DIR__ . '/bootstrap.php';

$pageTitle = 'Events';
$events = [];
$calendarEvents = [];

try {
    $events = db()->query('SELECT * FROM events ORDER BY date ASC, id DESC')->fetchAll();

    foreach ($events as $event) {
        $day = $event['date'];
        if (!isset($calendarEvents[$day])) {
            $calendarEvents[$day] = [];
        }
        $calendarEvents[$day][] = [
            'id' => (int) $event['id'],
            'title' => $event['title'],
            'type' => $event['type'],
            'description' => $event['description'],
        ];
    }
} catch (Throwable $exception) {
    flash('error', 'Events could not be loaded yet.');
}

include __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="section-title">
        <h2>Feast days and Sabbath calendar</h2>
        <?php if (auth_check() && (current_user()['role'] ?? 'believer') === 'admin'): ?>
            <a class="btn btn-primary" href="<?= e(app_url('admin/events.php')) ?>">Manage events</a>
        <?php endif; ?>
    </div>
</section>

<section class="section panel calendar-modern">
    <div class="section-title">
        <h2>Events calendar</h2>
        <span class="muted">Browse schedules by month and date</span>
    </div>

    <div class="calendar-controls">
        <button class="btn btn-ghost" type="button" id="calendarPrev"><i class="fa-solid fa-chevron-left"></i> Previous</button>
        <strong id="calendarMonthLabel">Month</strong>
        <button class="btn btn-ghost" type="button" id="calendarNext">Next <i class="fa-solid fa-chevron-right"></i></button>
    </div>

    <div class="calendar-grid" id="eventsCalendar"></div>

    <div class="panel calendar-detail-panel" style="margin-top: 1rem;">
        <div class="section-title">
            <h3 style="margin: 0;">Selected day</h3>
            <span class="muted" id="selectedDateLabel">No date selected</span>
        </div>
        <div id="selectedDateEvents" class="list-stack">
            <p class="muted">Click a date with a marker to view event details.</p>
        </div>
    </div>
</section>

<script>
    (() => {
        const eventsByDate = <?= json_encode($calendarEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const calendarEl = document.getElementById('eventsCalendar');
        const monthLabelEl = document.getElementById('calendarMonthLabel');
        const selectedDateLabelEl = document.getElementById('selectedDateLabel');
        const selectedDateEventsEl = document.getElementById('selectedDateEvents');
        const prevButton = document.getElementById('calendarPrev');
        const nextButton = document.getElementById('calendarNext');
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
                    document.querySelectorAll('.calendar-cell.active').forEach((item) => item.classList.remove('active'));
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
