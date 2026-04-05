<?php
/**
 * Admin layout header - FeyFay Media
 */
$settings = get_settings($pdo);
$site_name = e($settings['site_name'] ?? DEFAULT_SITE_NAME);
$admin_title = $admin_title ?? 'Dashboard';
$current_page = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
$is_active = function (array $pages) use ($current_page) {
    return in_array($current_page, $pages, true) ? 'is-active' : '';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($admin_title); ?> | <?php echo $site_name; ?> Admin</title>
    <link rel="icon" href="<?php echo base_url('assets/images/feyfaylogo.png'); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/responsive.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="admin-header-inner">
            <a href="<?php echo base_url('admin/dashboard.php'); ?>" class="admin-logo"><?php echo $site_name; ?></a>
            <button type="button" class="admin-nav-toggle" id="adminNavToggle" aria-label="Toggle menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <nav class="admin-nav" id="adminNav">
                <a class="<?php echo $is_active(['dashboard.php']); ?>" href="<?php echo base_url('admin/dashboard.php'); ?>"><svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg><span>Dashboard</span></a>
                <a class="<?php echo $is_active(['posts.php']); ?>" href="<?php echo base_url('admin/posts.php'); ?>"><svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 5h16v2H4V5zm0 6h16v2H4v-2zm0 6h10v2H4v-2z"/></svg><span>Event Updates</span></a>
                <a class="<?php echo $is_active(['add-post.php', 'edit-post.php']); ?>" href="<?php echo base_url('admin/add-post.php'); ?>"><svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19 11H13V5h-2v6H5v2h6v6h2v-6h6z"/></svg><span>Add Event</span></a>
                <a class="<?php echo $is_active(['categories.php']); ?>" href="<?php echo base_url('admin/categories.php'); ?>"><svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11 3H3v8h8V3zm10 0h-8v8h8V3zM11 13H3v8h8v-8zm10 0h-8v8h8v-8z"/></svg><span>Categories</span></a>
                <a class="<?php echo $is_active(['comments.php']); ?>" href="<?php echo base_url('admin/comments.php'); ?>"><svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 4h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7l-5 4V6a2 2 0 0 1 2-2z"/></svg><span>Comments</span></a>
                <?php if (can_manage_settings()): ?><a class="<?php echo $is_active(['settings.php']); ?>" href="<?php echo base_url('admin/settings.php'); ?>"><svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M19.14 12.94a7.994 7.994 0 0 0 .06-.94c0-.32-.02-.63-.06-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.547 7.547 0 0 0-1.63-.94l-.36-2.54A.5.5 0 0 0 13.9 2h-3.8a.5.5 0 0 0-.49.42l-.36 2.54c-.58.23-1.12.54-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.71 8.48a.5.5 0 0 0 .12.64l2.03 1.58c-.04.31-.06.62-.06.94s.02.63.06.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32c.13.22.39.31.6.22l2.39-.96c.5.4 1.05.72 1.63.94l.36 2.54c.04.24.25.42.49.42h3.8c.24 0 .45-.18.49-.42l.36-2.54c.58-.23 1.12-.54 1.63-.94l2.39.96c.22.09.47 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58zM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5z"/></svg><span>Settings</span></a><?php endif; ?>
                <?php if (can_manage_settings()): ?><a class="<?php echo $is_active(['radio.php']); ?>" href="<?php echo base_url('admin/radio.php'); ?>"><svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3 7h18v10H3V7zm2 2v6h14V9H5zm5 1h2v4h-2v-4zm-4 0h2v4H6v-4zm8 0h2v4h-2v-4zM8 3h8v2H8V3z"/></svg><span>Live Radio</span></a><?php endif; ?>
                <?php if (can_manage_users()): ?><a class="<?php echo $is_active(['users.php']); ?>" href="<?php echo base_url('admin/users.php'); ?>"><svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.98 1.97 3.45V19h7v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg><span>Staff</span></a><?php endif; ?>
                <a href="<?php echo base_url(); ?>" target="_blank" rel="noopener"><svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M14 3h7v7h-2V6.41l-9.29 9.3-1.42-1.42 9.3-9.29H14V3zM5 5h6v2H7v10h10v-4h2v6H5V5z"/></svg><span>View Site</span></a>
                <a href="<?php echo base_url('admin/logout.php'); ?>"><svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 17v-2h4V9h-4V7h4a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-4zm9-5-4 4v-3H8v-2h7V8l4 4zM3 5h7V3H3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7v-2H3V5z"/></svg><span>Logout</span></a>
            </nav>
        </div>
    </header>
    <main class="admin-main">
