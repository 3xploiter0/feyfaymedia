<?php
/**
 * Contact page - FeyFay Media
 * Contact form + newsletter subscription
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Contact';
$message = '';
$message_type = 'info';
$settings = get_settings($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['newsletter'])) {
        // Newsletter signup
        $email = trim($_POST['email'] ?? '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO subscribers (email) VALUES (?)");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $message = 'Thank you for subscribing.';
                    $message_type = 'success';
                } else {
                    $message = 'You are already subscribed.';
                    $message_type = 'info';
                }
            } catch (Exception $e) {
                $message = 'Subscription failed. Please try again.';
                $message_type = 'error';
            }
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['message'] ?? '');
        if ($name && $email && $body && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $to = trim($settings['contact_email'] ?? '') ?: 'tricksrayy@gmail.com';
            $subject_line = $subject ?: 'Contact form – ' . ($settings['site_name'] ?? 'FeyFay Media');
            $email_body = "Name: $name\nEmail: $email\n\nMessage:\n$body";
            $site_name = $settings['site_name'] ?? 'FeyFay Media';
            $sent = send_site_email($to, $subject_line, $email_body, $to, $site_name, $name . ' <' . $email . '>');
            $message = $sent ? 'Thank you. We will get back to you soon.' : 'Your message could not be sent. Please try again or email us directly.';
            $message_type = $sent ? 'success' : 'error';
        } else {
            $message = 'Please fill all required fields correctly.';
            $message_type = 'error';
        }
    }
}

if ($message !== '') {
    toast_add($message_type, $message);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container content-wrap page-shell">
    <div class="content-main static-page">
        <section class="page-intro">
            <p class="section-kicker">Contact</p>
            <h1 class="page-title">Get in touch</h1>
            <p class="page-description">Send a message, share a tip, or subscribe to the newsletter.</p>
        </section>
        <div class="surface-card">
        <form class="contact-form" method="post" action="">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required value="<?php echo e($_POST['name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="<?php echo e($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" value="<?php echo e($_POST['subject'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" rows="5" required><?php echo e($_POST['message'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
        </div>
    </div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
