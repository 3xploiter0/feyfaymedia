# FEYFAY Platform Hardening Notes

This document summarizes the professional hardening and UX upgrades applied to the platform.

## 1) Security Hardening

### Global response/session hardening
- Added baseline security headers in `includes/config.php`:
  - `X-Frame-Options: SAMEORIGIN`
  - `X-Content-Type-Options: nosniff`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
  - `Strict-Transport-Security` (only when HTTPS is active)
- Disabled `X-Powered-By` exposure where possible.
- Tightened session handling:
  - strict mode cookies
  - cookie-only sessions
  - periodic session ID regeneration

### CSRF protection expanded to public flows
- CSRF helpers were centralized in `includes/functions.php` and now apply to:
  - contact form (`contact.php`)
  - newsletter form (`includes/sidebar.php`)
  - public comment form (`post.php` -> `ajax/comment.php`)

### Anti-abuse + bot mitigation
- Added reusable rate-limit helper functions in `includes/functions.php` (IP/file-based buckets in temp storage).
- Applied rate limits to:
  - admin login (`admin/login.php`)
  - contact form (`contact.php`)
  - newsletter subscribe (`contact.php`)
  - public comments (`ajax/comment.php`)
- Added honeypot field checks to public forms for basic bot filtering.

### Content sanitization
- Added sanitizers in `includes/functions.php`:
  - `sanitize_plain_text()`
  - `sanitize_post_content()`
  - `sanitize_embed_code()`
  - `normalize_http_url()`
- Applied sanitizers to:
  - post create/edit (`admin/add-post.php`, `admin/edit-post.php`)
  - settings URLs and text (`admin/settings.php`)
  - radio embed/URLs (`admin/radio.php`)
  - comment/contact inputs (`ajax/comment.php`, `contact.php`)

### Apache hardening
- Added root `.htaccess`:
  - blocks directory listing
  - denies direct access to sensitive directories/files
  - adds defense-in-depth security headers
- Added upload directory `.htaccess`:
  - blocks PHP execution in `assets/images/uploads/posts/`

## 2) Professional UX/Structure Polish

### Admin navigation upgrade
- Added SVG icons to admin sidebar/mobile nav links.
- Added active-page highlighting in admin navigation.
- Maintained responsive behavior (left sidebar on desktop, drawer on mobile).

### Public navigation polish
- Added active-link highlighting in the public header menu.

### Editing experience reliability
- Improved sticky form behavior in edit flow so user input is preserved better after validation errors (`admin/edit-post.php`).

## 3) Operational Notes

- No schema migration is required for these hardening changes.
- For Apache deployments, ensure `AllowOverride All` is enabled so `.htaccess` rules are applied.
- If you are behind a reverse proxy/CDN, ensure real client IP forwarding is configured correctly.

## 4) Recommended Next Steps

- Enforce HTTPS in production and use secure certs.
- Replace default admin credentials immediately.
- Add periodic DB backups and log monitoring.
- Consider adding CAPTCHA for public comments if abuse volume rises.
