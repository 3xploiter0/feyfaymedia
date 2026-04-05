<?php
/**
 * Public sidebar - Trending, recent posts, ads, newsletter
 * Requires: $pdo
 */
$settings = get_settings($pdo);
$trending = get_trending_posts($pdo, 5);
$recent = get_posts($pdo, 5, 0, null);
$ads_sidebar = $settings['ads_sidebar'] ?? '';
$radio_name = trim($settings['radio_name'] ?? '') ?: 'Live Radio';
$radio_is_live = !empty($settings['radio_is_live']);
$stream_url = trim($settings['stream_url'] ?? '');
$embed_code = trim($settings['embed_code'] ?? '');
$now_playing = trim($settings['now_playing'] ?? '');
$radio_button_text = trim($settings['radio_button_text'] ?? '') ?: 'Listen Live';
$show_radio_widget = $radio_name && ($stream_url !== '' || $embed_code !== '');
?>
<aside class="sidebar">
    <div class="sidebar-block">
        <h3 class="sidebar-title">Find Events</h3>
        <p>Browse upcoming opportunities by city, type, and date.</p>
        <p><a class="btn btn-primary btn-small" href="<?php echo base_url('events.php'); ?>">Explore Events</a></p>
    </div>
    <?php if ($show_radio_widget): ?>
    <div class="sidebar-block radio-widget">
        <h3 class="sidebar-title"><?php echo e($radio_name); ?></h3>
        <?php if ($radio_is_live): ?>
        <span class="radio-widget-badge radio-live">Live</span>
        <?php if ($now_playing !== ''): ?><p class="radio-widget-now"><?php echo e($now_playing); ?></p><?php endif; ?>
        <p class="radio-widget-actions">
            <a href="<?php echo base_url('live-radio.php'); ?>#radio-player" class="btn btn-primary btn-small"><?php echo e($radio_button_text); ?></a>
        </p>
        <?php else: ?>
        <span class="radio-widget-badge radio-offline">Offline</span>
        <p><a href="<?php echo base_url('live-radio.php'); ?>">View radio page</a></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="sidebar-block">
        <h3 class="sidebar-title">Trending</h3>
        <ul class="sidebar-list">
            <?php foreach ($trending as $p): ?>
            <li><a href="<?php echo base_url('post.php?slug=' . e($p['slug'])); ?>"><?php echo e($p['title']); ?></a> <span class="views">(<?php echo (int)$p['views']; ?>)</span></li>
            <?php endforeach; ?>
            <?php if (empty($trending)): ?>
            <li>No trending posts yet.</li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="sidebar-block">
        <h3 class="sidebar-title">Recent Event Updates</h3>
        <ul class="sidebar-list">
            <?php foreach ($recent as $p): ?>
            <li><a href="<?php echo base_url('post.php?slug=' . e($p['slug'])); ?>"><?php echo e($p['title']); ?></a></li>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?>
            <li>No event updates yet.</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php if ($ads_sidebar): ?>
    <div class="sidebar-block ads ads-sidebar">
        <div class="ad-placeholder"><?php echo $ads_sidebar; ?></div>
    </div>
    <?php endif; ?>
    <div class="sidebar-block newsletter">
        <h3 class="sidebar-title">Event Updates</h3>
        <p>Subscribe for the latest events and announcements.</p>
        <form class="newsletter-form" action="<?php echo base_url('contact.php'); ?>" method="post" id="newsletterForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="newsletter" value="1">
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;opacity:0;">
            <input type="email" name="email" placeholder="Your email" required aria-label="Email">
            <button type="submit">Subscribe</button>
        </form>
    </div>
</aside>
