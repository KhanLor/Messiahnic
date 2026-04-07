        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <strong><?= e(APP_NAME) ?></strong>
                <p>A simple community platform for teachings, scripture study, events, prayer, and fellowship.</p>
            </div>
            <div>
                <strong>Explore</strong>
                <ul>
                    <li><a href="<?= e(app_url('teachings.php')) ?>">Teachings</a></li>
                    <li><a href="<?= e(app_url('scriptures.php')) ?>">Scriptures</a></li>
                    <li><a href="<?= e(app_url('events.php')) ?>">Events</a></li>
                    <li><a href="<?= e(app_url('churches.php')) ?>">Churches</a></li>
                    <li><a href="<?= e(app_url('about.php')) ?>">About</a></li>
                </ul>
            </div>
            <div>
                <strong>Community</strong>
                <ul>
                    <li><a href="<?= e(app_url('community.php')) ?>">Live Broadcast</a></li>
                    <li><a href="<?= e(app_url('prayer-requests.php')) ?>">Prayer Requests</a></li>
                    <li><a href="<?= e(app_url('notifications.php')) ?>">Notifications</a></li>
                </ul>
            </div>
        </div>
    </footer>
</div>

<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
