<?php
/**
 * Admin - Site settings - FeyFay Media
 * Site name, logo, description, contact, social, footer, ads
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role_admin();
$admin_title = 'Settings';
$settings = get_settings($pdo);
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { header('Location: settings.php'); exit; }
    $site_name = sanitize_plain_text($_POST['site_name'] ?? '', 100);
    if ($site_name === '') $site_name = DEFAULT_SITE_NAME;
    $site_description = trim((string)($_POST['site_description'] ?? ''));
    $site_description = strip_tags($site_description);
    $contact_email = strtolower(trim($_POST['contact_email'] ?? ''));
    $contact_email = ($contact_email !== '' && filter_var($contact_email, FILTER_VALIDATE_EMAIL)) ? $contact_email : null;
    $phone = sanitize_plain_text($_POST['phone'] ?? '', 50);
    $facebook = normalize_http_url($_POST['facebook'] ?? '');
    $twitter = normalize_http_url($_POST['twitter'] ?? '');
    $instagram = normalize_http_url($_POST['instagram'] ?? '');
    $youtube = normalize_http_url($_POST['youtube'] ?? '');
    $footer_text = strip_tags(trim((string)($_POST['footer_text'] ?? '')));
    $ads_header = str_replace("\0", '', trim((string)($_POST['ads_header'] ?? '')));
    $ads_sidebar = str_replace("\0", '', trim((string)($_POST['ads_sidebar'] ?? '')));
    $ads_article = str_replace("\0", '', trim((string)($_POST['ads_article'] ?? '')));
    $ads_homepage = str_replace("\0", '', trim((string)($_POST['ads_homepage'] ?? '')));
    $show_admin_link = isset($_POST['show_admin_link']) ? 1 : 0;

    $stmt = $pdo->prepare("UPDATE settings SET site_name=?, site_description=?, contact_email=?, phone=?, facebook=?, twitter=?, instagram=?, youtube=?, footer_text=?, ads_header=?, ads_sidebar=?, ads_article=?, ads_homepage=?, show_admin_link=? WHERE id=1");
    $stmt->execute([$site_name, $site_description, $contact_email, $phone, $facebook, $twitter, $instagram, $youtube, $footer_text, $ads_header, $ads_sidebar, $ads_article, $ads_homepage, $show_admin_link]);
    $success = 'Settings saved.';
    $settings = get_settings($pdo, true);
}

if ($success !== '') toast_add('success', $success);

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Settings</h1>
    <form method="post" class="admin-form settings-form">
        <?php echo csrf_field(); ?>
        <h2>Site</h2>
        <div class="form-group">
            <label for="site_name">Site Name</label>
            <input type="text" id="site_name" name="site_name" value="<?php echo e($settings['site_name'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="site_description">Site Description</label>
            <textarea id="site_description" name="site_description" rows="2"><?php echo e($settings['site_description'] ?? ''); ?></textarea>
        </div>
        <h2>Contact</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="contact_email">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" value="<?php echo e($settings['contact_email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?php echo e($settings['phone'] ?? ''); ?>">
            </div>
        </div>
        <h2>Social Links</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="facebook">Facebook URL</label>
                <input type="url" id="facebook" name="facebook" value="<?php echo e($settings['facebook'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="twitter">Twitter URL</label>
                <input type="url" id="twitter" name="twitter" value="<?php echo e($settings['twitter'] ?? ''); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="instagram">Instagram URL</label>
                <input type="url" id="instagram" name="instagram" value="<?php echo e($settings['instagram'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="youtube">YouTube URL</label>
                <input type="url" id="youtube" name="youtube" value="<?php echo e($settings['youtube'] ?? ''); ?>">
            </div>
        </div>
        <h2>Footer</h2>
        <div class="form-group">
            <label for="footer_text">Footer Text</label>
            <textarea id="footer_text" name="footer_text" rows="2"><?php echo e($settings['footer_text'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="show_admin_link" value="1" <?php echo !empty($settings['show_admin_link']) ? 'checked' : ''; ?>> Show admin login link (shield) in footer</label>
            <small>When enabled, a shield icon linking to the admin login page appears in the footer.</small>
        </div>
        <h2>Ad Placements (HTML or code)</h2>
        <div class="form-group">
            <label for="ads_header">Header Ad</label>
            <textarea id="ads_header" name="ads_header" rows="3" placeholder="Paste ad code or HTML"><?php echo e($settings['ads_header'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="ads_sidebar">Sidebar Ad</label>
            <textarea id="ads_sidebar" name="ads_sidebar" rows="3"><?php echo e($settings['ads_sidebar'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="ads_article">In-Article Ad</label>
            <textarea id="ads_article" name="ads_article" rows="3"><?php echo e($settings['ads_article'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="ads_homepage">Homepage Ad (between Latest Event Updates and category sections)</label>
            <textarea id="ads_homepage" name="ads_homepage" rows="3"><?php echo e($settings['ads_homepage'] ?? ''); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
