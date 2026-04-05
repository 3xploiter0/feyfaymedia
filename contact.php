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
$ip = client_ip();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Security validation failed. Please refresh and try again.';
        $message_type = 'error';
    } elseif (!empty($_POST['website'])) {
        // Honeypot trap: respond quietly without processing.
        $message = 'Thank you. Your request was received.';
        $message_type = 'success';
    } elseif (!empty($_POST['newsletter'])) {
        // Newsletter signup
        $email = strtolower(trim($_POST['email'] ?? ''));
        $bucket = 'newsletter:' . $ip;
        if (rate_limit_too_many($bucket, 8, 3600)) {
            $message = 'Too many subscription attempts. Please try again later.';
            $message_type = 'error';
        } else {
            rate_limit_hit($bucket, 3600);
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
        }
    } else {
        $name = sanitize_plain_text($_POST['name'] ?? '', 120);
        $email = strtolower(trim($_POST['email'] ?? ''));
        $subject = sanitize_plain_text($_POST['subject'] ?? '', 160);
        $body = trim((string)($_POST['message'] ?? ''));
        $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $body);
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($body, 'UTF-8') > 4000) $body = mb_substr($body, 0, 4000, 'UTF-8');
        } else {
            if (strlen($body) > 4000) $body = substr($body, 0, 4000);
        }
        $bucket = 'contact:' . $ip;
        if (rate_limit_too_many($bucket, 5, 900)) {
            $message = 'Too many messages sent recently. Please try again in a few minutes.';
            $message_type = 'error';
        } else {
            rate_limit_hit($bucket, 900);
        if ($name && $email && $body && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $to = trim($settings['contact_email'] ?? '') ?: 'tricksrayy@gmail.com';
            $subject_line = $subject ?: 'Contact form – ' . ($settings['site_name'] ?? 'FEYFAY INVESTMENT');
            $email_body = "Name: $name\nEmail: $email\n\nMessage:\n$body";
            $site_name = $settings['site_name'] ?? 'FEYFAY INVESTMENT';
            $sent = send_site_email($to, $subject_line, $email_body, $to, $site_name, $name . ' <' . $email . '>');
            $message = $sent ? 'Thank you. We will get back to you soon.' : 'Your message could not be sent. Please try again or email us directly.';
            $message_type = $sent ? 'success' : 'error';
        } else {
            $message = 'Please fill all required fields correctly.';
            $message_type = 'error';
        }
        }
    }
}

if ($message !== '') {
    toast_add($message_type, $message);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container content-wrap">
    <div class="content-main static-page">
        <h1 class="page-title">Contact</h1>
        <form class="contact-form" method="post" action="">
            <?php echo csrf_field(); ?>
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;">
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
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
