<?php
/**
 * Public site header - FeyFay Media
 * Requires: $pdo, $page_title (optional). Optional SEO: $canonical_url, $og_*, $twitter_*
 */
$settings = get_settings($pdo);
$site_name = e($settings['site_name'] ?? DEFAULT_SITE_NAME);
$site_desc = e($settings['site_description'] ?? DEFAULT_SITE_DESCRIPTION);
$page_title = isset($page_title) ? e($page_title) . ' | ' . $site_name : $site_name;
$meta_description = isset($meta_description) ? $meta_description : $site_desc;
$ads_header = $settings['ads_header'] ?? '';
$site_logo = !empty($settings['site_logo']) ? $settings['site_logo'] : 'assets/images/feyfaylogo.png';
$categories = get_categories($pdo);
$req = isset($_SERVER['REQUEST_URI']) ? ltrim($_SERVER['REQUEST_URI'], '/') : '';
$canonical_url = isset($canonical_url) ? $canonical_url : base_url($req ?: '');
$current_script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$current_slug = trim((string)($_GET['slug'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" href="<?php echo base_url('assets/images/feyfaylogo.png'); ?>" type="image/png">
    <meta name="description" content="<?php echo e($meta_description); ?>">
    <link rel="canonical" href="<?php echo e($canonical_url); ?>">
    <?php if (!empty($og_title)): ?>
    <meta property="og:type" content="<?php echo e($og_type ?? 'article'); ?>">
    <meta property="og:title" content="<?php echo e($og_title); ?>">
    <meta property="og:description" content="<?php echo e($og_description ?? $meta_description); ?>">
    <meta property="og:url" content="<?php echo e($canonical_url); ?>">
    <meta property="og:site_name" content="<?php echo e($site_name); ?>">
    <?php if (!empty($og_image)): ?><meta property="og:image" content="<?php echo e($og_image); ?>"><?php endif; ?>
    <meta name="twitter:card" content="<?php echo e($twitter_card ?? 'summary_large_image'); ?>">
    <meta name="twitter:title" content="<?php echo e($twitter_title ?? $og_title ?? $page_title); ?>">
    <meta name="twitter:description" content="<?php echo e($twitter_description ?? $og_description ?? $meta_description); ?>">
    <?php if (!empty($og_image)): ?><meta name="twitter:image" content="<?php echo e($og_image); ?>"><?php endif; ?>
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/responsive.css'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header">
    <div class="header-top">
        <div class="container header-inner">
            <a href="<?php echo base_url(); ?>" class="logo">
                <img src="<?php echo base_url(e($site_logo)); ?>" alt="<?php echo $site_name; ?>">
            </a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle menu"><span></span><span></span><span></span></button>
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li><a class="<?php echo ($current_script === 'index.php' || $current_script === '') ? 'is-active' : ''; ?>" href="<?php echo base_url(); ?>">Home</a></li>
                    <li><a class="<?php echo $current_script === 'events.php' ? 'is-active' : ''; ?>" href="<?php echo base_url('events.php'); ?>">Events</a></li>
                    <?php foreach ($categories as $cat): ?>
                    <li><a class="<?php echo ($current_script === 'category.php' && $current_slug === (string)$cat['slug']) ? 'is-active' : ''; ?>" href="<?php echo base_url('category.php?slug=' . e($cat['slug'])); ?>"><?php echo e($cat['name']); ?></a></li>
                    <?php endforeach; ?>
                    <li><a class="<?php echo $current_script === 'about.php' ? 'is-active' : ''; ?>" href="<?php echo base_url('about.php'); ?>">About</a></li>
                    <li><a class="<?php echo $current_script === 'live-radio.php' ? 'is-active' : ''; ?>" href="<?php echo base_url('live-radio.php'); ?>">Live Radio</a></li>
                    <li><a class="<?php echo $current_script === 'contact.php' ? 'is-active' : ''; ?>" href="<?php echo base_url('contact.php'); ?>">Contact</a></li>
                </ul>
                <form class="header-search" action="<?php echo base_url('search.php'); ?>" method="get" role="search">
                    <input type="search" name="q" placeholder="Search events..." value="<?php echo e($_GET['q'] ?? ''); ?>" aria-label="Search event updates">
                    <button type="submit">Search</button>
                </form>
            </nav>
        </div>
    </div>
    <?php if ($ads_header): ?>
    <div class="ads ads-header container">
        <div class="ad-placeholder"><?php echo $ads_header; ?></div>
    </div>
    <?php endif; ?>
</header>

<main class="main-content">
