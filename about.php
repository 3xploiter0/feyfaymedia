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

<div class="container content-wrap">
    <div class="content-main static-page">
        <article>
            <h1 class="page-title">About <?php echo $site_name; ?></h1>
            <div class="static-content">
                <p><?php echo $site_name; ?> ni jukwaa rasmi la kukuletea taarifa sahihi za matukio na shughuli mbalimbali zinazofanyika Tanzania na nje ya mipaka yake.</p>
                <h2>Our Mission</h2>
                <p>Kukuunganisha na fursa za kujenga mahusiano, mtandao wa kazi, huduma, na ushirikiano kupitia taarifa za matukio yaliyothibitishwa.</p>
                <h2>Contact</h2>
                <p><a href="<?php echo base_url('contact.php'); ?>">Get in touch</a> for tips or inquiries.</p>
            </div>
        </article>
    </div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
