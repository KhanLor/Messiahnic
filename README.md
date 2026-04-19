# Messiahnic Believer Website

## Setup

1. Create a MySQL database named `messiahnic`.
2. Import `schema.sql` into that database.
3. Update the database credentials in `bootstrap.php` if your local XAMPP settings differ.
4. Open the site at `http://localhost/Messiahnic/`.

### Existing installations

If your database already exists from an older version, run this once to add church map support:

```sql
CREATE TABLE IF NOT EXISTS church_locations (
	id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(180) NOT NULL,
	address VARCHAR(255) NOT NULL,
	city VARCHAR(120) NOT NULL,
	pastor_name VARCHAR(180) NULL,
	latitude DECIMAL(10, 7) NOT NULL,
	longitude DECIMAL(10, 7) NOT NULL,
	contact VARCHAR(120) NULL,
	description TEXT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX idx_church_locations_name (name),
	INDEX idx_church_locations_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE church_locations ADD COLUMN pastor_name VARCHAR(180) NULL AFTER city;
```

## Default admin

- Username: `Admin` (or Email: `admin@messiahnic.local`)
- Password: `123`

## Included modules

- Authentication with PHP sessions and hashed passwords
- Profile editing for believers
- Teachings with media uploads, comments, search, and categories
- Scripture browsing with search and highlighted passages
- Feast day and Sabbath event calendar
- Prayer requests with admin approval
- Community discussion forum
- Notification feed
- Admin dashboard and management screens
- Churches map with admin-managed locations and search (Google Maps API)

## Google Maps setup

1. Create a Google Cloud project and enable "Maps JavaScript API".
2. Create an API key and restrict it by HTTP referrer (recommended).
3. Set environment variable `MESSIAH_GOOGLE_MAPS_API_KEY` to your key.
4. Restart Apache in XAMPP.