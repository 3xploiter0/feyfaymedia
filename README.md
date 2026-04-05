# FeyFay Media - News Blog CMS

FeyFay Media is a PHP + MySQL news CMS with a public news site and an admin dashboard for staff/admin publishing workflows.

## Features

### Public site
- Homepage with breaking ticker, featured hero, latest grid, category blocks, and sidebar widgets
- Dedicated `events.php` page with filters (status, city, type, category, date range)
- Single post page with tags, related posts, sponsored badge, comments, and SEO metadata
- Scheduled publishing support (future posts become visible at `published_at`)
- Category archive and paginated search
- Contact form + newsletter subscribe
- Live radio page (`live-radio.php`) with stream/embed support and live status
- Sticky live radio player bar on public pages when radio is live
- Dynamic sitemap (`sitemap.php`) and `robots.txt`

### Admin
- Role-based access: `staff` and `admin`
- Secure login/logout with session regeneration and CSRF token
- Post management with TinyMCE editor, tags, featured/sponsored flags, draft/published/scheduled states
- Event metadata management per update: type, city, venue, start/end datetime, event status
- Comment moderation (approve/delete)
- Category management (admin-only add/delete)
- Site settings and ad zones (admin-only)
- Live radio settings (admin-only): stream URL, embed code, now playing, live toggle, button text
- Staff management (admin-only): create user, role, active flag, password reset

### Technical
- PHP (no framework), PDO prepared statements, MySQL/MariaDB
- CSRF checks on state-changing admin actions
- File upload validation for post images
- Escaped output with `htmlspecialchars()`
- Indexed queries for public post ordering and trending
- Security hardening: public-form CSRF, rate limiting, anti-bot honeypot, stricter session handling, Apache upload-execution blocking

## Requirements

- PHP 7.4+ with PDO MySQL
- MySQL 5.7+ or MariaDB
- Apache/Nginx or PHP built-in server
- Internet access for CDN assets used in admin editor (`tinymce`) and Google Fonts

## Installation

1. Create database/tables from the primary schema:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
   Or from MySQL client:
   ```sql
   source /path/to/FeyFay/sql/schema.sql
   ```
2. Configure DB credentials in `includes/db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'feyfay_media');
   define('DB_USER', 'your_user');
   define('DB_PASS', 'your_password');
   ```
3. Ensure upload directory exists:
   ```bash
   mkdir -p assets/images/uploads/posts
   chmod 755 assets/images/uploads/posts
   ```
4. Run locally:
   ```bash
   cd /path/to/FeyFay
   php -S localhost:8000
   ```
   Open `http://localhost:8000`.
5. If upgrading an older database, run:
   ```bash
   mysql -u your_user -p feyfay_media < sql/migrate_production.sql
   ```

Note: `database.sql` exists as an older schema snapshot. Use `sql/schema.sql` for new installs.

## Default Admin Login

- URL: `http://yoursite/admin/login.php`
- Username: `admin`
- Password: `password`

Change the default password before production use.

## Project Structure

```
FeyFay/
├── admin/
│   ├── add-post.php, edit-post.php, posts.php, delete-post.php
│   ├── categories.php, comments.php, settings.php, radio.php, users.php
│   ├── login.php, logout.php
│   └── includes/header.php, footer.php
├── ajax/
│   ├── comment.php, increment-view.php, search.php
├── assets/
│   ├── css/style.css, admin.css, responsive.css
│   ├── js/main.js, admin.js
│   └── images/uploads/posts/
├── includes/
│   ├── auth.php, config.php, db.php, functions.php, pagination.php
│   └── header.php, footer.php, sidebar.php
├── sql/
│   ├── schema.sql
│   └── migrate_production.sql
├── index.php, post.php, category.php, search.php
├── events.php
├── live-radio.php, about.php, contact.php, 404.php
├── sitemap.php, robots.txt
├── USER-GUIDE.md
└── README.md
```

## Database Tables

- `users`: account profile, password hash, role (`staff|admin`), `is_active`
- `categories`: category name + slug
- `posts`: content, SEO, featured/sponsored flags, `published_at`, views
- `posts` event fields: `is_event`, `event_type`, `event_city`, `event_location`, `event_start_at`, `event_end_at`, `event_status`
- `tags`, `post_tags`: tag system
- `comments`: pending/approved moderation queue
- `settings`: site identity, social links, ads, footer/admin link toggle, live radio fields
- `subscribers`: newsletter emails

## Ads and Radio

In admin settings, ad HTML can be configured for:
- Header
- Sidebar
- In-article
- Homepage

Live radio is configured in `Admin -> Live Radio` and displayed on:
- `live-radio.php`
- Sidebar radio widget
- Sticky bottom player (when live stream is enabled)

## Production Checklist

1. Change default admin credentials.
2. Run `sql/migrate_production.sql` for existing deployments.
3. Update `robots.txt` with your real sitemap URL.
4. Submit `https://yourdomain/sitemap.php` to search consoles.
5. Set DB credentials and mail settings for your environment.
6. Review `HARDENING-NOTES.md` and enable `.htaccess` support (`AllowOverride All`) on Apache.

## GitHub Push Checklist

1. Initialize git in this folder:
   ```bash
   git init
   ```
2. Commit project:
   ```bash
   git add .
   git commit -m "Initial commit: FeyFay Media CMS"
   ```
3. Create your GitHub repo, then connect and push:
   ```bash
   git branch -M main
   git remote add origin https://github.com/<your-username>/<your-repo>.git
   git push -u origin main
   ```

## License

Use freely for your projects.
