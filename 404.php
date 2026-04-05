<?php
/**
 * 404 Not Found - FeyFay Media
 */
http_response_code(404);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Page Not Found';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container content-wrap page-shell">
    <div class="content-main">
        <section class="page-intro">
            <p class="section-kicker">Not found</p>
            <h1 class="page-title">404</h1>
            <p class="page-description">The page you requested could not be found.</p>
        </section>
        <section class="section error-404 surface-card">
            <p class="no-posts">The page you are looking for could not be found.</p>
            <p><a href="<?php echo base_url(); ?>" class="btn btn-primary">Back to Home</a></p>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
