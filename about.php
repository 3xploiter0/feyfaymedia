<?php
/**
 * About page - FeyFay Media
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$settings = get_settings($pdo);
$page_title = 'About Us';
$site_name = e($settings['site_name'] ?? DEFAULT_SITE_NAME);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container content-wrap page-shell">
    <div class="content-main static-page">
        <section class="page-intro">
            <p class="section-kicker">About</p>
            <h1 class="page-title">About <?php echo $site_name; ?></h1>
            <p class="page-description"><?php echo e($settings['site_description'] ?? DEFAULT_SITE_DESCRIPTION); ?></p>
        </section>
        <article class="surface-card">
            <div class="static-content">
                <p><?php echo $site_name; ?> publishes timely stories, features, and updates with a cleaner editorial presentation and a focus on clear reading.</p>
                <h2>Our Mission</h2>
                <p>To share useful, readable journalism that keeps the experience professional and easy to follow.</p>
                <h2>Contact</h2>
                <p><a href="<?php echo base_url('contact.php'); ?>">Get in touch</a> for tips or inquiries.</p>
            </div>
        </article>
    </div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
