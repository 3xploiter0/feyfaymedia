<?php
/**
 * Helper functions - FeyFay Media News Blog CMS
 */

/**
 * Generate URL-friendly slug
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = @iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: 'post');
}

/**
 * Truncate text safely for excerpts
 */
function excerpt($text, $length = 160) {
    $text = strip_tags($text);
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Format date for display
 */
function format_date($date) {
    return date('F j, Y', strtotime($date));
}

function format_datetime($date) {
    return date('M j, Y g:i A', strtotime($date));
}

/**
 * Escape for HTML (XSS protection)
 */
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// --- Security + input helpers ---

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify() {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        return $token !== '' && hash_equals((string) csrf_token(), (string) $token);
    }
}

function client_ip() {
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];
    foreach ($candidates as $raw) {
        if ($raw === '') continue;
        $parts = explode(',', $raw);
        foreach ($parts as $p) {
            $ip = trim($p);
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function sanitize_plain_text($text, $max_len = 255) {
    $text = trim((string)$text);
    $text = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    if ($max_len > 0) {
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') > $max_len) $text = mb_substr($text, 0, $max_len, 'UTF-8');
        } else {
            if (strlen($text) > $max_len) $text = substr($text, 0, $max_len);
        }
    }
    return trim($text);
}

function sanitize_post_content($html) {
    $html = (string)$html;
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*("|\').*?\1/iu', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*[^\s>]+/iu', '', $html);
    $html = preg_replace('/(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/iu', '$1="#"', $html);
    $html = preg_replace('/(href|src)\s*=\s*([\'"])\s*data:text\/html[^\'"]*\2/iu', '$1="#"', $html);
    $allowed = '<p><br><strong><b><em><i><u><s><blockquote><ul><ol><li><a><h2><h3><h4><h5><h6><img><figure><figcaption><iframe><hr><pre><code><span><div>';
    $html = strip_tags($html, $allowed);
    return trim($html);
}

function sanitize_embed_code($html) {
    $html = (string)$html;
    if (trim($html) === '') return '';
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*("|\').*?\1/iu', '', $html);
    $html = preg_replace('/\son\w+\s*=\s*[^\s>]+/iu', '', $html);
    $allowed = '<iframe><audio><source><div><span><p><a><br>';
    return trim(strip_tags($html, $allowed));
}

function normalize_http_url($url) {
    $url = trim((string)$url);
    if ($url === '') return '';
    if (!preg_match('#^https?://#i', $url)) return '';
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

function rate_limit_storage_file($key) {
    $hash = hash('sha256', (string)$key);
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'feyfay_rl_' . $hash . '.json';
}

function rate_limit_read_hits($key, $window_seconds) {
    $path = rate_limit_storage_file($key);
    $now = time();
    $window_start = $now - max(1, (int)$window_seconds);
    if (!is_file($path)) return [];
    $raw = @file_get_contents($path);
    $data = is_string($raw) ? json_decode($raw, true) : [];
    if (!is_array($data)) $data = [];
    $hits = [];
    foreach ($data as $t) {
        $ts = (int)$t;
        if ($ts >= $window_start && $ts <= $now) $hits[] = $ts;
    }
    return $hits;
}

function rate_limit_too_many($key, $limit, $window_seconds) {
    $hits = rate_limit_read_hits($key, $window_seconds);
    return count($hits) >= max(1, (int)$limit);
}

function rate_limit_hit($key, $window_seconds) {
    $hits = rate_limit_read_hits($key, $window_seconds);
    $hits[] = time();
    @file_put_contents(rate_limit_storage_file($key), json_encode(array_values($hits)), LOCK_EX);
}

function rate_limit_clear($key) {
    $path = rate_limit_storage_file($key);
    if (is_file($path)) @unlink($path);
}

/**
 * Base URL of the site (works from root and from /admin)
 */
function base_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = dirname($script);
    if (strpos($base, '/admin') !== false) {
        $base = dirname($base);
    }
    $base = rtrim($protocol . '://' . $host . $base, '/');
    return $path !== '' ? $base . '/' . ltrim($path, '/') : $base;
}

/**
 * Ensure upload directory exists and is writable (returns true if ok)
 */
function ensure_upload_dir() {
    $path = defined('UPLOAD_PATH') ? UPLOAD_PATH : (dirname(__DIR__) . '/assets/images/uploads/posts/');
    if (!is_dir($path)) {
        $parent = dirname($path);
        if (!is_dir($parent)) {
            @mkdir(dirname($parent), 0755, true);
        }
        return @mkdir($path, 0755, true);
    }
    return is_writable($path);
}

/**
 * Get safe file extension from MIME type for uploads
 */
function mime_to_ext($mime) {
    $map = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    return $map[$mime] ?? 'jpg';
}

/**
 * Redirect and exit
 */
function redirect($url, $code = 302) {
    header('Location: ' . $url, true, $code);
    exit;
}

/**
 * Toast notifications (current request + flash across redirects)
 */
function toast_add($type, $message, $flash = false) {
    $message = trim((string)$message);
    if ($message === '') return;

    $type = strtolower(trim((string)$type));
    $allowed = ['success', 'error', 'info', 'warning'];
    if (!in_array($type, $allowed, true)) $type = 'info';

    $toast = ['type' => $type, 'message' => $message];
    if ($flash) {
        if (!isset($_SESSION['toast_notifications']) || !is_array($_SESSION['toast_notifications'])) {
            $_SESSION['toast_notifications'] = [];
        }
        $_SESSION['toast_notifications'][] = $toast;
        return;
    }

    if (!isset($GLOBALS['toast_notifications']) || !is_array($GLOBALS['toast_notifications'])) {
        $GLOBALS['toast_notifications'] = [];
    }
    $GLOBALS['toast_notifications'][] = $toast;
}

function toast_add_flash($type, $message) {
    toast_add($type, $message, true);
}

function toast_consume_all() {
    $toasts = [];

    if (!empty($_SESSION['toast_notifications']) && is_array($_SESSION['toast_notifications'])) {
        foreach ($_SESSION['toast_notifications'] as $t) {
            if (!is_array($t)) continue;
            $msg = trim((string)($t['message'] ?? ''));
            if ($msg === '') continue;
            $type = strtolower((string)($t['type'] ?? 'info'));
            if (!in_array($type, ['success', 'error', 'info', 'warning'], true)) $type = 'info';
            $toasts[] = ['type' => $type, 'message' => $msg];
        }
    }
    unset($_SESSION['toast_notifications']);

    if (!empty($GLOBALS['toast_notifications']) && is_array($GLOBALS['toast_notifications'])) {
        foreach ($GLOBALS['toast_notifications'] as $t) {
            if (!is_array($t)) continue;
            $msg = trim((string)($t['message'] ?? ''));
            if ($msg === '') continue;
            $type = strtolower((string)($t['type'] ?? 'info'));
            if (!in_array($type, ['success', 'error', 'info', 'warning'], true)) $type = 'info';
            $toasts[] = ['type' => $type, 'message' => $msg];
        }
    }
    $GLOBALS['toast_notifications'] = [];

    return $toasts;
}

function render_toasts_script() {
    $toasts = toast_consume_all();
    if (empty($toasts)) return '';

    $json = json_encode($toasts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || $json === 'null') return '';

    return '<script>(function(){var toasts=' . $json . ';function run(){if(!window.FeyFayToast||typeof window.FeyFayToast.show!=="function")return;toasts.forEach(function(t){window.FeyFayToast.show(t.message,t.type);});}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",run);}else{run();}})();</script>';
}

/**
 * Send email via SMTP (e.g. MailHog on localhost:1025). Returns true on success.
 */
function send_mail_smtp($to, $subject, $body, $from_email, $from_name = '', $reply_to = '') {
    if (!defined('MAIL_SMTP_HOST') || MAIL_SMTP_HOST === '' || !defined('MAIL_SMTP_PORT')) {
        return false;
    }
    $host = MAIL_SMTP_HOST;
    $port = (int) MAIL_SMTP_PORT;
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 5);
    if (!$fp) return false;
    $read = function () use ($fp) {
        $line = @fgets($fp, 512);
        return $line !== false ? trim($line) : '';
    };
    $write = function ($cmd) use ($fp) { @fwrite($fp, $cmd . "\r\n"); };
    if (strpos($read(), '220') !== 0) { fclose($fp); return false; }
    $write('HELO localhost');
    if (strpos($read(), '250') !== 0) { fclose($fp); return false; }
    $write('MAIL FROM:<' . $from_email . '>');
    if (strpos($read(), '250') !== 0) { fclose($fp); return false; }
    $write('RCPT TO:<' . $to . '>');
    if (strpos($read(), '250') !== 0) { fclose($fp); return false; }
    $write('DATA');
    if (strpos($read(), '354') !== 0) { fclose($fp); return false; }
    $headers = 'From: ' . ($from_name ? $from_name . ' <' . $from_email . '>' : $from_email) . "\r\n";
    if ($reply_to) $headers .= 'Reply-To: ' . $reply_to . "\r\n";
    $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
    $headers .= 'Subject: ' . $subject . "\r\n";
    $write($headers . "\r\n" . $body);
    $write('.');
    $last = $read();
    fclose($fp);
    return strpos($last, '250') === 0;
}

/**
 * Send contact/notification email. Uses SMTP if MAIL_SMTP_HOST is set, else PHP mail().
 */
function send_site_email($to, $subject, $body, $from_email, $from_name = '', $reply_to = '') {
    if (defined('MAIL_SMTP_HOST') && MAIL_SMTP_HOST !== '') {
        return send_mail_smtp($to, $subject, $body, $from_email, $from_name, $reply_to);
    }
    $headers = [
        'From: ' . ($from_name ? $from_name . ' <' . $from_email . '>' : $from_email),
        'Content-Type: text/plain; charset=UTF-8',
        'MIME-Version: 1.0',
    ];
    if ($reply_to) $headers[] = 'Reply-To: ' . $reply_to;
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Get site settings (single row id=1). Pass true as second arg to force reload (e.g. after update).
 */
function get_settings($pdo, $reload = false) {
    static $settings = null;
    if ($reload) $settings = null;
    if ($settings === null) {
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $settings = $stmt->fetch();
    }
    return $settings ?: [];
}

/**
 * Get all categories (cached per request to reduce queries)
 */
function get_categories($pdo) {
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $cache = $stmt->fetchAll();
    return $cache;
}

/**
 * SQL condition for posts visible on the public site (published and, if scheduled, published_at in the past)
 * $alias = table alias used in the query (e.g. 'p')
 */
function posts_public_visibility_sql($alias = 'p') {
    return "($alias.status = 'published' AND ($alias.published_at IS NULL OR $alias.published_at <= NOW()))";
}

/**
 * ORDER BY for public posts (scheduled posts sort by when they go live)
 */
function posts_public_order_sql($alias = 'p') {
    return "COALESCE($alias.published_at, $alias.created_at) DESC";
}

/**
 * SQL expression to resolve event status from stored value/date fields.
 */
function event_status_sql_expr($alias = 'p') {
    return "CASE
        WHEN $alias.is_event = 0 THEN NULL
        WHEN $alias.event_status IS NOT NULL AND $alias.event_status <> '' THEN $alias.event_status
        WHEN $alias.event_start_at IS NULL THEN 'upcoming'
        WHEN $alias.event_start_at > NOW() THEN 'upcoming'
        WHEN $alias.event_end_at IS NOT NULL AND $alias.event_end_at < NOW() THEN 'completed'
        ELSE 'ongoing'
    END";
}

function event_effective_status($post) {
    if (empty($post['is_event'])) return null;
    $status = strtolower(trim((string)($post['event_status'] ?? '')));
    if (in_array($status, ['upcoming', 'ongoing', 'completed', 'cancelled'], true)) {
        return $status;
    }
    $start = !empty($post['event_start_at']) ? strtotime((string)$post['event_start_at']) : false;
    $end = !empty($post['event_end_at']) ? strtotime((string)$post['event_end_at']) : false;
    $now = time();
    if ($start === false || $start > $now) return 'upcoming';
    if ($end !== false && $end < $now) return 'completed';
    return 'ongoing';
}

function event_status_label($status) {
    $status = strtolower((string)$status);
    $map = [
        'upcoming' => 'Upcoming',
        'ongoing' => 'Ongoing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    return $map[$status] ?? 'Event';
}

function format_event_datetime($datetime) {
    if (empty($datetime)) return '';
    return date('M j, Y g:i A', strtotime((string)$datetime));
}

function format_event_date_range($start_at, $end_at = null) {
    if (empty($start_at)) return 'Date TBA';
    $start_ts = strtotime((string)$start_at);
    if ($start_ts === false) return 'Date TBA';
    if (empty($end_at)) return date('M j, Y g:i A', $start_ts);
    $end_ts = strtotime((string)$end_at);
    if ($end_ts === false) return date('M j, Y g:i A', $start_ts);
    if (date('Ymd', $start_ts) === date('Ymd', $end_ts)) {
        return date('M j, Y g:i A', $start_ts) . ' - ' . date('g:i A', $end_ts);
    }
    return date('M j, Y g:i A', $start_ts) . ' - ' . date('M j, Y g:i A', $end_ts);
}

function normalize_event_filters(array $filters) {
    $normalized = [];
    $normalized['q'] = sanitize_plain_text($filters['q'] ?? '', 120);
    $status = strtolower(trim((string)($filters['status'] ?? '')));
    $normalized['status'] = in_array($status, ['upcoming', 'ongoing', 'completed', 'cancelled'], true) ? $status : '';
    $normalized['city'] = sanitize_plain_text($filters['city'] ?? '', 120);
    $normalized['type'] = sanitize_plain_text($filters['type'] ?? '', 80);
    $normalized['category_id'] = (int)($filters['category_id'] ?? 0);
    $date_from = trim((string)($filters['date_from'] ?? ''));
    $normalized['date_from'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from) ? $date_from : '';
    $date_to = trim((string)($filters['date_to'] ?? ''));
    $normalized['date_to'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to) ? $date_to : '';
    return $normalized;
}

function build_event_filters_sql(array $filters, &$params, $alias = 'p') {
    $filters = normalize_event_filters($filters);
    $status_expr = event_status_sql_expr($alias);
    $sql = " AND $alias.is_event = 1";

    if ($filters['status'] !== '') {
        $sql .= " AND ($status_expr = ?)";
        $params[] = $filters['status'];
    }
    if ($filters['city'] !== '') {
        $sql .= " AND $alias.event_city = ?";
        $params[] = $filters['city'];
    }
    if ($filters['type'] !== '') {
        $sql .= " AND $alias.event_type = ?";
        $params[] = $filters['type'];
    }
    if ($filters['category_id'] > 0) {
        $sql .= " AND $alias.category_id = ?";
        $params[] = $filters['category_id'];
    }
    if ($filters['date_from'] !== '') {
        $sql .= " AND $alias.event_start_at >= ?";
        $params[] = $filters['date_from'] . ' 00:00:00';
    }
    if ($filters['date_to'] !== '') {
        $sql .= " AND $alias.event_start_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59';
    }
    if ($filters['q'] !== '') {
        $term = '%' . $filters['q'] . '%';
        $sql .= " AND ($alias.title LIKE ? OR $alias.summary LIKE ? OR $alias.content LIKE ? OR $alias.event_location LIKE ? OR $alias.event_city LIKE ? OR $alias.event_type LIKE ?)";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }
    return $sql;
}

function get_event_posts($pdo, array $filters = [], $limit = 12, $offset = 0) {
    $params = [];
    $status_expr = event_status_sql_expr('p');
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name, $status_expr AS event_status_resolved
            FROM posts p
            JOIN categories c ON p.category_id = c.id
            JOIN users u ON p.author_id = u.id
            WHERE " . posts_public_visibility_sql('p');
    $sql .= build_event_filters_sql($filters, $params, 'p');
    $sql .= " ORDER BY FIELD($status_expr, 'ongoing', 'upcoming', 'completed', 'cancelled'), p.event_start_at ASC, p.created_at DESC
             LIMIT ? OFFSET ?";
    $params[] = (int)$limit;
    $params[] = (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function count_event_posts($pdo, array $filters = []) {
    $params = [];
    $sql = "SELECT COUNT(*) FROM posts p WHERE " . posts_public_visibility_sql('p');
    $sql .= build_event_filters_sql($filters, $params, 'p');
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function get_event_filter_options($pdo) {
    $cities = [];
    $types = [];
    $city_stmt = $pdo->query("SELECT DISTINCT event_city FROM posts p WHERE " . posts_public_visibility_sql('p') . " AND p.is_event = 1 AND p.event_city IS NOT NULL AND p.event_city <> '' ORDER BY event_city ASC");
    while ($row = $city_stmt->fetch()) {
        $cities[] = $row['event_city'];
    }
    $type_stmt = $pdo->query("SELECT DISTINCT event_type FROM posts p WHERE " . posts_public_visibility_sql('p') . " AND p.is_event = 1 AND p.event_type IS NOT NULL AND p.event_type <> '' ORDER BY event_type ASC");
    while ($row = $type_stmt->fetch()) {
        $types[] = $row['event_type'];
    }
    return ['cities' => $cities, 'types' => $types];
}

/**
 * Get display status for admin: 'draft', 'scheduled', or 'published'
 */
function post_display_status($post) {
    if (empty($post['status']) || $post['status'] === 'draft') return 'draft';
    if (!empty($post['published_at']) && strtotime($post['published_at']) > time()) return 'scheduled';
    return 'published';
}

/**
 * Get category by id or slug
 */
function get_category($pdo, $id_or_slug) {
    $col = is_numeric($id_or_slug) ? 'id' : 'slug';
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE $col = ?");
    $stmt->execute([$id_or_slug]);
    return $stmt->fetch();
}

/**
 * Get published posts with pagination (only posts visible on public site: published + scheduled in past)
 */
function get_posts($pdo, $limit = 10, $offset = 0, $category_id = null, $featured_only = false) {
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name 
            FROM posts p 
            JOIN categories c ON p.category_id = c.id 
            JOIN users u ON p.author_id = u.id 
            WHERE " . posts_public_visibility_sql('p');
    $params = [];
    if ($category_id) {
        $sql .= " AND p.category_id = ?";
        $params[] = $category_id;
    }
    if ($featured_only) {
        $sql .= " AND p.is_featured = 1";
    }
    $sql .= " ORDER BY " . posts_public_order_sql('p') . " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get single post by id or slug (public: published and visible, i.e. scheduled time in past or publish now)
 */
function get_post($pdo, $id_or_slug, $public_only = true) {
    $col = is_numeric($id_or_slug) ? 'p.id' : 'p.slug';
    $val = $id_or_slug;
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name 
            FROM posts p 
            JOIN categories c ON p.category_id = c.id 
            JOIN users u ON p.author_id = u.id 
            WHERE $col = ?";
    if ($public_only) $sql .= " AND " . posts_public_visibility_sql('p');
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$val]);
    return $stmt->fetch();
}

/**
 * Get featured post (one featured article for hero; only if visible on public site)
 */
function get_featured_post($pdo) {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name 
                           FROM posts p 
                           JOIN categories c ON p.category_id = c.id 
                           JOIN users u ON p.author_id = u.id 
                           WHERE " . posts_public_visibility_sql('p') . " AND p.is_featured = 1 
                           ORDER BY " . posts_public_order_sql('p') . " LIMIT 1");
    $stmt->execute();
    return $stmt->fetch();
}

/**
 * Breaking news: latest 5 published posts (for ticker; only visible on public site)
 */
function get_breaking_news($pdo, $limit = 5) {
    $stmt = $pdo->prepare("SELECT id, title, slug, created_at, published_at FROM posts WHERE " . posts_public_visibility_sql('posts') . " ORDER BY " . posts_public_order_sql('posts') . " LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Trending: most viewed published posts (only visible on public site)
 */
function get_trending_posts($pdo, $limit = 5) {
    $stmt = $pdo->prepare("SELECT p.id, p.title, p.slug, p.views FROM posts p WHERE " . posts_public_visibility_sql('p') . " ORDER BY p.views DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Related posts (same category, exclude current; only visible on public site)
 */
function get_related_posts($pdo, $post_id, $category_id, $limit = 3) {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug 
                          FROM posts p JOIN categories c ON p.category_id = c.id 
                          WHERE p.category_id = ? AND p.id != ? AND " . posts_public_visibility_sql('p') . " 
                          ORDER BY " . posts_public_order_sql('p') . " LIMIT ?");
    $stmt->execute([$category_id, $post_id, $limit]);
    return $stmt->fetchAll();
}

/**
 * Search published posts (with optional pagination; only visible on public site)
 */
function search_posts($pdo, $q, $limit = 20, $offset = 0) {
    $term = '%' . $q . '%';
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug, u.name AS author_name 
                           FROM posts p 
                           JOIN categories c ON p.category_id = c.id 
                           JOIN users u ON p.author_id = u.id 
                           WHERE " . posts_public_visibility_sql('p') . " 
                           AND (p.title LIKE ? OR p.summary LIKE ? OR p.content LIKE ? OR p.event_location LIKE ? OR p.event_city LIKE ? OR p.event_type LIKE ?) 
                           ORDER BY " . posts_public_order_sql('p') . " LIMIT ? OFFSET ?");
    $stmt->execute([$term, $term, $term, $term, $term, $term, $limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Count search results (for pagination; only visible on public site)
 */
function count_search_posts($pdo, $q) {
    $term = '%' . $q . '%';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts p 
                           WHERE " . posts_public_visibility_sql('p') . " 
                           AND (p.title LIKE ? OR p.summary LIKE ? OR p.content LIKE ? OR p.event_location LIKE ? OR p.event_city LIKE ? OR p.event_type LIKE ?)");
    $stmt->execute([$term, $term, $term, $term, $term, $term]);
    return (int) $stmt->fetchColumn();
}

/**
 * Get latest posts for multiple categories (one query for homepage category sections)
 */
function get_posts_by_category_ids($pdo, $category_ids, $per_category = 3) {
    if (empty($category_ids)) return [];
    $placeholders = implode(',', array_fill(0, count($category_ids), '?'));
    $limit = count($category_ids) * $per_category;
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug 
                           FROM posts p 
                           JOIN categories c ON p.category_id = c.id 
                           WHERE " . posts_public_visibility_sql('p') . " AND p.category_id IN ($placeholders) 
                           ORDER BY p.category_id, " . posts_public_order_sql('p'));
    $stmt->execute(array_values($category_ids));
    $all = $stmt->fetchAll();
    $grouped = [];
    foreach ($all as $row) {
        $cid = $row['category_id'];
        if (!isset($grouped[$cid])) $grouped[$cid] = [];
        if (count($grouped[$cid]) < $per_category) $grouped[$cid][] = $row;
    }
    return $grouped;
}

/**
 * Count published posts (only visible on public site)
 */
function count_posts($pdo, $category_id = null) {
    $sql = "SELECT COUNT(*) FROM posts WHERE " . posts_public_visibility_sql('posts');
    $params = [];
    if ($category_id) {
        $sql .= " AND category_id = ?";
        $params[] = $category_id;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Get tags for a post
 */
function get_tags_for_post($pdo, $post_id) {
    $stmt = $pdo->prepare("SELECT t.id, t.name, t.slug FROM tags t INNER JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

/**
 * Get approved comments for a post
 */
function get_post_comments($pdo, $post_id) {
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC");
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

/**
 * Count comments (all or by status)
 */
function count_comments($pdo, $status = null) {
    $sql = "SELECT COUNT(*) FROM comments";
    $params = [];
    if ($status) {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Increment post view count
 */
function increment_post_views($pdo, $post_id) {
    $stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
}
