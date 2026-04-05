<?php
/**
 * Admin - Live Radio / Online Radio settings - FeyFay Media
 * Manage radio name, description, stream URL, embed code, status, now playing, button text
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role_admin();
$admin_title = 'Live Radio';
$settings = get_settings($pdo);
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: radio.php');
        exit;
    }
    $radio_name = sanitize_plain_text($_POST['radio_name'] ?? '', 100);
    $radio_description = strip_tags(trim((string)($_POST['radio_description'] ?? '')));
    $stream_url = normalize_http_url($_POST['stream_url'] ?? '');
    $embed_code = sanitize_embed_code($_POST['embed_code'] ?? '');
    $radio_is_live = isset($_POST['radio_is_live']) ? 1 : 0;
    $now_playing = sanitize_plain_text($_POST['now_playing'] ?? '', 255);
    $radio_button_text = sanitize_plain_text($_POST['radio_button_text'] ?? '', 50) ?: 'Listen Live';

    $stmt = $pdo->prepare("UPDATE settings SET radio_name=?, radio_description=?, stream_url=?, embed_code=?, radio_is_live=?, now_playing=?, radio_button_text=? WHERE id=1");
    $stmt->execute([$radio_name ?: null, $radio_description ?: null, $stream_url ?: null, $embed_code ?: null, $radio_is_live, $now_playing ?: null, $radio_button_text]);
    $success = 'Radio settings saved.';
    $settings = get_settings($pdo, true);
}

if ($success !== '') toast_add('success', $success);

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Live Radio Settings</h1>
    <p class="admin-lead">Configure your online radio stream. Visitors can listen from the <a href="<?php echo base_url('live-radio.php'); ?>" target="_blank">Live Radio</a> page.</p>

    <form method="post" class="admin-form radio-settings-form">
        <?php echo csrf_field(); ?>

        <h2>Basic info</h2>
        <div class="form-group">
            <label for="radio_name">Radio name</label>
            <input type="text" id="radio_name" name="radio_name" value="<?php echo e($settings['radio_name'] ?? ''); ?>" placeholder="e.g. FeyFay Radio">
            <small>Shown as the title on the Live Radio page and in the sidebar widget.</small>
        </div>
        <div class="form-group">
            <label for="radio_description">Short description</label>
            <textarea id="radio_description" name="radio_description" rows="3" placeholder="A short line about your station"><?php echo e($settings['radio_description'] ?? ''); ?></textarea>
            <small>Displayed below the title on the Live Radio page.</small>
        </div>

        <h2>Stream URL (recommended)</h2>
        <div class="form-group">
            <label for="stream_url">Stream URL</label>
            <input type="url" id="stream_url" name="stream_url" value="<?php echo e($settings['stream_url'] ?? ''); ?>" placeholder="https://example-stream-url.com/live">
            <small>Paste your live stream URL here (e.g. Icecast, Shoutcast, or any direct audio stream). The site will use an HTML5 audio player. Example: <code>https://stream.example.com/radio</code></small>
        </div>

        <h2>Optional: Custom embed code</h2>
        <div class="form-group">
            <label for="embed_code">Embed code</label>
            <textarea id="embed_code" name="embed_code" rows="4" placeholder="<iframe ... or third-party player HTML"><?php echo e($settings['embed_code'] ?? ''); ?></textarea>
            <small>If you use a third-party player (e.g. TuneIn, Radio.co embed), paste the HTML here. When set, this is shown instead of the default audio player. Leave empty to use the Stream URL above.</small>
        </div>

        <h2>Status &amp; display</h2>
        <div class="form-group">
            <label><input type="checkbox" name="radio_is_live" value="1" <?php echo !empty($settings['radio_is_live']) ? 'checked' : ''; ?>> Radio is live</label>
            <small>When checked, the stream is shown as "Live" and the player is available. When unchecked, visitors see "Radio is currently offline".</small>
        </div>
        <div class="form-group">
            <label for="now_playing">Now playing text</label>
            <input type="text" id="now_playing" name="now_playing" value="<?php echo e($settings['now_playing'] ?? ''); ?>" placeholder="e.g. Morning Show with John">
            <small>Optional. Shown as "Now Playing" on the Live Radio page and in the sidebar widget.</small>
        </div>
        <div class="form-group">
            <label for="radio_button_text">Button text</label>
            <input type="text" id="radio_button_text" name="radio_button_text" value="<?php echo e($settings['radio_button_text'] ?? 'Listen Live'); ?>" placeholder="Listen Live">
            <small>Text for the main listen button (e.g. "Listen Live", "Play").</small>
        </div>

        <button type="submit" class="btn btn-primary">Save radio settings</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
