        </div>
    </main>

    <?php $footerUser = current_user(); ?>
    <?php $footerIsAdmin = $footerUser && (($footerUser['role'] ?? 'believer') === 'admin'); ?>
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
            <div>
                <strong>Admin</strong>
                <ul>
                    <?php if ($footerIsAdmin): ?>
                        <li><a href="<?= e(app_url('admin/dashboard.php')) ?>">Admin Dashboard</a></li>
                        <li><a href="<?= e(app_url('auth/logout.php')) ?>">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= e(app_url('auth/login.php')) ?>">Admin Login</a></li>
                    <?php endif; ?>
                </ul>
                <strong style="display: block; margin-top: 0.85rem;">Follow us on</strong>
                <ul>
                    <li><a href="https://www.facebook.com/profile.php?id=61586394952761" target="_blank" rel="noopener"><i class="fa-brands fa-facebook"></i> Facebook Page 1</a></li>
                    <li><a href="https://www.facebook.com/MSNBFE" target="_blank" rel="noopener"><i class="fa-brands fa-facebook"></i> Facebook Page 2</a></li>
                </ul>
            </div>
            <div>
                <strong>Get in touch</strong>
                <ul>
                    <li><i class="fa-solid fa-location-dot"></i> Mulig, Davao City, Davao del Sur</li>
                    <li><a href="mailto:messiahsacredname@gmail.com"><i class="fa-solid fa-envelope"></i> messiahsacredname@gmail.com</a></li>
                    <li><i class="fa-solid fa-phone"></i> Contact Number: <a href="tel:09971350655">09971350655</a></li>
                    <li><i class="fa-solid fa-phone"></i> Contact Number: <a href="tel:09975744832">09975744832</a></li>
                </ul>
            </div>
        </div>
    </footer>
</div>

<script src="<?= e(app_url('assets/js/app.js')) ?>"></script>
</body>
</html>
