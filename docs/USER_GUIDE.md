# Messiahnic System User Guide

This site is a PHP/MySQL church and fellowship platform for Messiahnic Believers. It includes public pages, member features, and an admin area for managing content.

## What You Need

- XAMPP or another PHP 8+ and MySQL setup
- Apache and MySQL running
- A MySQL database named `messiahnic`

## First-Time Setup

1. Copy the project into your web root, for example `c:\xampp\htdocs\Messiahnic`.
2. Create the `messiahnic` database in MySQL.
3. Import `schema.sql` into that database.
4. Check `bootstrap.php` if your local database username, password, host, or database name are different.
5. Open the site in your browser at `http://localhost/Messiahnic/`.

## Default Admin Login

The current seeded admin account is:

- Email: `admin@messiahnic.local`
- Password: `Admin123!`

If you restore an older database, the admin credentials may be different depending on the seed you imported.

## Public Pages

These pages can be viewed without logging in:

- Home page: overall site introduction and navigation
- About: mission, faith summary, and congregation information
- Leadership: leader details and executive members
- Teachings: sermon and teaching library with search and categories
- Scriptures: searchable scripture browsing with highlighted passages
- Events: calendar-style view of upcoming events
- Churches: church location listings and map view
- Community: live broadcast page
- Notifications: site updates and announcements
- Prayer Requests: request list and submission flow

## Member Use

1. Go to the login page under `auth/login.php`.
2. Sign in using your username or email and password.
3. After login, you can access your profile and member-only actions.
4. Use the logout button when you are done.

Member accounts can normally:

- View and update profile details
- Browse teachings and scriptures
- Submit or view prayer requests, depending on permissions
- Follow church updates and announcements

## Admin Use

After logging in as an admin, the top navigation shows an Admin link that opens the dashboard.

From the admin dashboard you can manage:

- Users: activate, deactivate, and assign roles
- Teachings: create teaching posts and upload media files
- Scriptures: add verses and mark key passages as highlighted
- Events: manage Sabbath and feast day posts
- Churches: add map locations, contact details, and images
- Prayer requests: approve or delete submissions
- Community: set the live broadcast URL
- Notifications: create and publish site alerts
- About: edit congregation information and faith summary
- Leadership: update leader details and executive member profiles

## Media And Uploads

Uploaded files are stored in the `uploads/` directory. Make sure the web server can write to that folder, especially if you plan to add teachings, profile photos, or church images.

## Optional Integrations

### Google Maps

The Churches page can display a map when you provide a Google Maps API key.

Set this environment variable:

- `MESSIAH_GOOGLE_MAPS_API_KEY`

### Chatbot

If you use the chatbot page, configure:

- `MESSIAH_GEMINI_API_KEY`
- `MESSIAH_GEMINI_MODEL` (optional)

## Environment Variables

`bootstrap.php` reads the following database settings:

- `MESSIAH_DB_HOST`
- `MESSIAH_DB_NAME`
- `MESSIAH_DB_USER`
- `MESSIAH_DB_PASS`

If these are not set, the app falls back to common XAMPP defaults.

## Troubleshooting

- If pages fail to load, confirm Apache and MySQL are running.
- If you see database errors, verify the `messiahnic` database exists and `schema.sql` was imported.
- If the churches map is blank, check the Google Maps API key.
- If uploads fail, confirm the `uploads/` folder is writable.
- If the admin menu does not appear, confirm you are logged in as a user with the admin role.

## Quick Page Map

- Public homepage: `index.php`
- About: `about.php`
- Leadership: `leadership.php`
- Teachings: `teachings.php`
- Scriptures: `scriptures.php`
- Events: `events.php`
- Churches: `churches.php`
- Community: `community.php`
- Notifications: `notifications.php`
- Prayer requests: `prayer-requests.php`
- Profile: `profile.php`

## Admin Page Map

- Dashboard: `admin/dashboard.php`
- Users: `admin/users.php`
- Teachings: `admin/teachings.php`
- Scriptures: `admin/scriptures.php`
- Events: `admin/events.php`
- Churches: `admin/churches.php`
- Prayer requests: `admin/prayer-requests.php`
- Community: `admin/community.php`
- Notifications: `admin/notifications.php`
- About: `admin/about.php`
- Leadership: `admin/leadership.php`

## Notes

This guide reflects the current application structure in this repository. If new modules are added later, update this file so it stays accurate.