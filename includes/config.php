<?php
/**
 * Site configuration - FeyFay Media
 * Constants used when settings table is not yet loaded
 */

// Hide PHP signature where possible
@ini_set('expose_php', '0');

// Baseline security headers for all responses
if (!headers_sent()) {
    header_remove('X-Powered-By');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Session start with secure options (once per request)
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
    if (empty($_SESSION['created_at'])) {
        $_SESSION['created_at'] = time();
        $_SESSION['last_regenerated_at'] = time();
        session_regenerate_id(true);
    } else {
        $last_regenerated = (int)($_SESSION['last_regenerated_at'] ?? 0);
        if ($last_regenerated === 0 || (time() - $last_regenerated) > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regenerated_at'] = time();
        }
    }
}

// Paths (use __FILE__ so path is correct when config is included from admin or root)
define('ROOT_PATH', rtrim(realpath(dirname(__DIR__)) ?: dirname(__DIR__), DIRECTORY_SEPARATOR) . '/');
define('UPLOAD_DIR', 'assets/images/uploads/posts');
define('UPLOAD_PATH', ROOT_PATH . UPLOAD_DIR . '/');
define('UPLOAD_URL', UPLOAD_DIR . '/');

// Pagination
define('POSTS_PER_PAGE', 12);
define('ADMIN_POSTS_PER_PAGE', 15);

// Defaults (overridden by settings table when available)
define('DEFAULT_SITE_NAME', 'FEYFAY INVESTMENT');
define('DEFAULT_SITE_DESCRIPTION', 'Jukwaa rasmi la taarifa sahihi kuhusu matukio na fursa za kuunganisha jamii.');

// Mail: set these to use MailHog (view at http://localhost:8025). Leave empty to use PHP mail().
define('MAIL_SMTP_HOST', 'localhost');  // e.g. 'localhost' for MailHog
define('MAIL_SMTP_PORT', 1025);          // MailHog SMTP port
