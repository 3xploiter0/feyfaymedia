<?php
/**
 * Dynamic sitemap.xml - FeyFay Media
 * Outputs XML sitemap for SEO (posts, categories, static pages)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$base = rtrim(base_url(), '/');

// Homepage
echo "  <url>\n    <loc>" . $base . "/</loc>\n    <changefreq>daily</changefreq>\n    <priority>1.0</priority>\n  </url>\n";

// Static pages
$static = [['events.php', 'daily', '0.9'], ['about.php', 'monthly', '0.7'], ['live-radio.php', 'weekly', '0.7'], ['contact.php', 'monthly', '0.6']];
foreach ($static as $s) {
    echo "  <url>\n    <loc>" . $base . '/' . $s[0] . "</loc>\n    <changefreq>{$s[1]}</changefreq>\n    <priority>{$s[2]}</priority>\n  </url>\n";
}

// Categories
$categories = get_categories($pdo);
foreach ($categories as $c) {
    $loc = $base . '/category.php?slug=' . rawurlencode($c['slug']);
    echo "  <url>\n    <loc>" . htmlspecialchars($loc) . "</loc>\n    <changefreq>daily</changefreq>\n    <priority>0.8</priority>\n  </url>\n";
}

// Published posts
$stmt = $pdo->query("SELECT slug, updated_at FROM posts WHERE " . posts_public_visibility_sql('posts') . " ORDER BY " . posts_public_order_sql('posts'));
while ($row = $stmt->fetch()) {
    $loc = $base . '/post.php?slug=' . rawurlencode($row['slug']);
    $lastmod = date('Y-m-d', strtotime($row['updated_at']));
    echo "  <url>\n    <loc>" . htmlspecialchars($loc) . "</loc>\n    <lastmod>{$lastmod}</lastmod>\n    <changefreq>weekly</changefreq>\n    <priority>0.9</priority>\n  </url>\n";
}

echo '</urlset>';
