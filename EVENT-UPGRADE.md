# Event-First Upgrade Notes

This document describes the event-focused platform upgrade (content model, filters, homepage, and admin flow).

## 1) Database model changes

Added event columns to `posts`:
- `is_event` (`TINYINT(1)`)
- `event_type` (`VARCHAR(80)`)
- `event_city` (`VARCHAR(120)`)
- `event_location` (`VARCHAR(255)`)
- `event_start_at` (`DATETIME`)
- `event_end_at` (`DATETIME`)
- `event_status` (`ENUM('upcoming','ongoing','completed','cancelled')`)

Added indexes:
- `idx_is_event_start (is_event, event_start_at)`
- `idx_event_status (event_status)`
- `idx_event_city (event_city)`
- `idx_event_type (event_type)`

Files:
- `sql/schema.sql`
- `sql/migrate_production.sql`

## 2) Public platform changes

### New events listing page
- Added `events.php` with filters:
  - keyword
  - event status
  - city
  - type
  - category
  - date range (`from`, `to`)
- Uses pagination and public visibility rules.

### Header navigation
- Added `Events` item in the main nav.
- Active-state highlighting now includes `events.php`.

### Homepage
- Added `Upcoming Events` section near the top.
- Added `View all events` CTA.
- Improved latest updates feed logic so featured update is excluded by ID (not by offset).
- Reduced category block count for cleaner visual hierarchy.

### Event context in cards/pages
- Event status/date badges and meta lines shown in:
  - homepage cards
  - category listing cards
  - search listing cards
  - single post detail card (`post.php`) when the item is marked as event

### Sidebar
- Added “Find Events” CTA block linking to `events.php`.

Files:
- `events.php`
- `index.php`
- `post.php`
- `category.php`
- `search.php`
- `includes/header.php`
- `includes/sidebar.php`
- `sitemap.php`

## 3) Admin changes

### Add/Edit Event Update forms
- Added event fields:
  - `This is an event`
  - event type
  - event status
  - city
  - venue/location
  - start datetime
  - end datetime
- Event section is shown/hidden dynamically from checkbox state.
- Validation added:
  - start datetime required for events
  - end datetime must be after start datetime

### Save logic
- `INSERT`/`UPDATE` now persist event fields.
- Existing non-event updates continue to work and save with null event metadata.

### Admin listing/dashboard
- Posts table now includes event column and event start datetime.
- Dashboard includes:
  - total events
  - upcoming/ongoing count
  - recent table includes event start column

Files:
- `admin/add-post.php`
- `admin/edit-post.php`
- `admin/posts.php`
- `admin/dashboard.php`

## 4) Query/service layer updates

Added event helper/query functions:
- `event_status_sql_expr()`
- `event_effective_status()`
- `event_status_label()`
- `format_event_datetime()`
- `format_event_date_range()`
- `normalize_event_filters()`
- `get_event_posts()`
- `count_event_posts()`
- `get_event_filter_options()`

Search also now checks event fields (`event_location`, `event_city`, `event_type`).

File:
- `includes/functions.php`

## 5) Deployment steps

For existing deployments, run migration:

```bash
mysql -u <user> -p feyfay_media < sql/migrate_production.sql
```

Then clear browser cache and refresh.

## 6) Backward compatibility

- Existing content remains valid.
- Non-event updates still appear in normal update flows.
- Event-specific pages and badges only activate for rows with `is_event = 1`.
