<?php
/**
 * Live Radio / Online Radio - FeyFay Media
 * Public page: stream player, status, now playing
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Live Radio';
$settings = get_settings($pdo);
$radio_name = trim($settings['radio_name'] ?? '') ?: 'Live Radio';
$radio_description = trim($settings['radio_description'] ?? '');
$stream_url = trim($settings['stream_url'] ?? '');
$embed_code = trim($settings['embed_code'] ?? '');
$is_live = !empty($settings['radio_is_live']);
$now_playing = trim($settings['now_playing'] ?? '');
$button_text = trim($settings['radio_button_text'] ?? '') ?: 'Listen Live';

$show_player = $is_live && ($stream_url !== '' || $embed_code !== '');

require_once __DIR__ . '/includes/header.php';
?>

<div class="container content-wrap page-shell">
    <div class="content-main static-page radio-page">
        <section class="page-intro">
            <p class="section-kicker">Radio</p>
            <h1 class="page-title"><?php echo e($radio_name); ?></h1>
            <p class="page-description"><?php echo e($radio_description ?: 'Listen to live radio and station updates.'); ?></p>
        </section>
        <div class="surface-card">

        <div class="radio-status-wrap">
            <?php if ($show_player): ?>
            <span class="radio-badge radio-live">Live</span>
            <?php else: ?>
            <span class="radio-badge radio-offline">Offline</span>
            <?php endif; ?>
        </div>

        <?php if ($show_player): ?>
            <?php if ($embed_code !== ''): ?>
            <div id="radio-player" class="radio-embed">
                <div class="radio-embed-inner">
                    <?php echo $embed_code; ?>
                </div>
            </div>
            <?php elseif ($stream_url !== ''): ?>
            <div id="radio-player" class="radio-player-card">
                <div class="radio-player-inner">
                    <audio id="radioAudio" class="radio-audio" controls preload="none">
                        <source src="<?php echo e($stream_url); ?>" type="audio/mpeg">
                        <source src="<?php echo e($stream_url); ?>" type="audio/aac">
                        <p>Your browser does not support the audio element. <a href="<?php echo e($stream_url); ?>">Open stream</a>.</p>
                    </audio>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($now_playing !== ''): ?>
            <p class="radio-now-playing"><strong>Now playing:</strong> <?php echo e($now_playing); ?></p>
            <?php endif; ?>

            <?php if ($embed_code === '' && $stream_url !== ''): ?>
            <p class="radio-actions">
                <button type="button" id="listen-live-btn" class="btn btn-primary"><?php echo e($button_text); ?></button>
            </p>
            <?php endif; ?>
        <?php else: ?>
        <div class="radio-offline-message">
            <p>Radio is currently offline. Please check back later.</p>
        </div>
        <?php endif; ?>
        </div>
    </div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php if ($show_player && $stream_url !== '' && $embed_code === ''): ?>
<script>
(function() {
    var playerEl = document.getElementById('radio-player');
    var audio = document.getElementById('radioAudio');
    var listenBtn = document.getElementById('listen-live-btn');

    function scrollAndPlay() {
        if (playerEl) {
            playerEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        if (audio) {
            audio.play().catch(function() {});
        }
    }

    if (listenBtn) {
        listenBtn.addEventListener('click', function() {
            scrollAndPlay();
        });
    }

    if (window.location.hash === '#radio-player') {
        if (playerEl) playerEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (audio) audio.play().catch(function() {});
    }
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
