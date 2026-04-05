<?php
/**
 * Homepage - FeyFay Media
 * Breaking ticker, featured hero, latest grid, category sections, sidebar
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Home';
// Optimized: minimal queries for above-the-fold data
$breaking = get_breaking_news($pdo, 5);
$featured = get_featured_post($pdo);
if (!$featured) {
    $latest_one = get_posts($pdo, 1, 0, null);
    $featured = $latest_one[0] ?? null;
}
$ongoing_events = get_event_posts($pdo, ['status' => 'ongoing'], 3, 0);
$upcoming_events = get_event_posts($pdo, ['status' => 'upcoming'], 6, 0);
$event_highlights = [];
$event_seen = [];
foreach (array_merge($ongoing_events, $upcoming_events) as $ev) {
    if (isset($event_seen[$ev['id']])) continue;
    $event_seen[$ev['id']] = true;
    $event_highlights[] = $ev;
    if (count($event_highlights) >= 6) break;
}

$latest = get_posts($pdo, 10, 0, null);
if ($featured) {
    $latest = array_values(array_filter($latest, function ($row) use ($featured) {
        return (int)$row['id'] !== (int)$featured['id'];
    }));
}
$latest = array_slice($latest, 0, 6);
$categories = get_categories($pdo);
$category_ids = array_slice(array_column($categories, 'id'), 0, 3);
$posts_by_category = get_posts_by_category_ids($pdo, $category_ids, 3);
$settings = get_settings($pdo);
$ads_homepage = $settings['ads_homepage'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breaking updates ticker -->
<?php if (!empty($breaking)): ?>
<div class="breaking-bar">
    <div class="container">
        <span class="breaking-label">Updates</span>
        <div class="breaking-ticker">
            <?php foreach ($breaking as $i => $b): ?>
            <a href="<?php echo base_url('post.php?slug=' . e($b['slug'])); ?>"><?php echo e($b['title']); ?></a><?php if ($i < count($breaking) - 1): ?> &bull; <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Hero / Featured -->
<?php if ($featured): ?>
<section class="hero featured-section">
    <div class="container">
        <article class="featured-article">
            <a href="<?php echo base_url('post.php?slug=' . e($featured['slug'])); ?>" class="featured-image-wrap">
                <?php if (!empty($featured['image'])): ?>
                <img src="<?php echo base_url($featured['image']); ?>" alt="<?php echo e($featured['title']); ?>" loading="eager" fetchpriority="high">
                <?php else: ?>
                <div class="featured-placeholder"></div>
                <?php endif; ?>
            </a>
            <div class="featured-body">
                <a href="<?php echo base_url('category.php?slug=' . e($featured['category_slug'])); ?>" class="cat-badge"><?php echo e($featured['category_name']); ?></a>
                <h1 class="featured-title"><a href="<?php echo base_url('post.php?slug=' . e($featured['slug'])); ?>"><?php echo e($featured['title']); ?></a></h1>
                <?php if (!empty($featured['is_event'])): ?>
                <?php $featured_status = event_effective_status($featured); ?>
                <div class="event-inline-meta">
                    <span class="event-status-badge event-status-<?php echo e($featured_status); ?>"><?php echo e(event_status_label($featured_status)); ?></span>
                    <span><?php echo e(format_event_date_range($featured['event_start_at'], $featured['event_end_at'])); ?></span>
                </div>
                <?php endif; ?>
                <p class="featured-excerpt"><?php echo e(excerpt($featured['summary'] ?: $featured['content'], 200)); ?></p>
                <div class="featured-meta"><?php echo format_date($featured['created_at']); ?> &middot; <?php echo e($featured['author_name']); ?></div>
            </div>
        </article>
    </div>
</section>
<?php endif; ?>

<div class="container content-wrap">
    <div class="content-main">
        <section class="section latest-section">
            <div class="section-head-row">
                <h2 class="section-heading">Upcoming Events</h2>
                <a class="section-more-link" href="<?php echo base_url('events.php'); ?>">View all events</a>
            </div>
            <?php if (empty($event_highlights)): ?>
            <p class="no-posts">No upcoming events published yet.</p>
            <?php else: ?>
            <div class="posts-grid">
                <?php foreach ($event_highlights as $event): ?>
                <?php $ev_status = event_effective_status($event); ?>
                <article class="post-card event-card">
                    <a href="<?php echo base_url('post.php?slug=' . e($event['slug'])); ?>" class="post-card-img">
                        <?php if (!empty($event['image'])): ?>
                        <img src="<?php echo base_url($event['image']); ?>" alt="<?php echo e($event['title']); ?>" loading="lazy">
                        <?php else: ?>
                        <div class="img-placeholder"></div>
                        <?php endif; ?>
                    </a>
                    <div class="post-card-body">
                        <div class="post-card-meta-top">
                            <a href="<?php echo base_url('category.php?slug=' . e($event['category_slug'])); ?>" class="cat-badge"><?php echo e($event['category_name']); ?></a>
                            <span class="event-status-badge event-status-<?php echo e($ev_status); ?>"><?php echo e(event_status_label($ev_status)); ?></span>
                        </div>
                        <h3><a href="<?php echo base_url('post.php?slug=' . e($event['slug'])); ?>"><?php echo e($event['title']); ?></a></h3>
                        <p class="excerpt"><?php echo e(excerpt($event['summary'] ?: $event['content'], 110)); ?></p>
                        <div class="event-inline-meta">
                            <span><?php echo e(format_event_date_range($event['event_start_at'], $event['event_end_at'])); ?></span>
                            <?php if (!empty($event['event_location']) || !empty($event['event_city'])): ?>
                            <span><?php echo e(trim(($event['event_location'] ?? '') . (!empty($event['event_city']) ? ', ' . $event['event_city'] : ''))); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Latest event updates -->
        <section class="section latest-section">
            <h2 class="section-heading">Latest Event Updates</h2>
            <?php if (empty($latest) && !$featured): ?>
            <p class="no-posts">No event updates yet. Check back soon.</p>
            <?php elseif (!empty($latest)): ?>
            <div class="posts-grid">
                <?php foreach ($latest as $post): ?>
                <article class="post-card">
                    <a href="<?php echo base_url('post.php?slug=' . e($post['slug'])); ?>" class="post-card-img">
                        <?php if (!empty($post['image'])): ?>
                        <img src="<?php echo base_url($post['image']); ?>" alt="<?php echo e($post['title']); ?>" loading="lazy">
                        <?php else: ?>
                        <div class="img-placeholder"></div>
                        <?php endif; ?>
                    </a>
                    <div class="post-card-body">
                        <div class="post-card-meta-top">
                            <a href="<?php echo base_url('category.php?slug=' . e($post['category_slug'])); ?>" class="cat-badge"><?php echo e($post['category_name']); ?></a>
                            <?php if (!empty($post['is_sponsored'])): ?><span class="sponsored-badge">Sponsored</span><?php endif; ?>
                        </div>
                        <h3><a href="<?php echo base_url('post.php?slug=' . e($post['slug'])); ?>"><?php echo e($post['title']); ?></a></h3>
                        <?php if (!empty($post['is_event'])): ?>
                        <?php $status = event_effective_status($post); ?>
                        <div class="event-inline-meta">
                            <span class="event-status-badge event-status-<?php echo e($status); ?>"><?php echo e(event_status_label($status)); ?></span>
                            <span><?php echo e(format_event_date_range($post['event_start_at'], $post['event_end_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                        <p class="excerpt"><?php echo e(excerpt($post['summary'] ?: $post['content'], 120)); ?></p>
                        <span class="meta"><?php echo format_date($post['created_at']); ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <?php if ($ads_homepage): ?>
        <section class="section ads-section">
            <div class="ads ads-homepage">
                <div class="ad-placeholder"><?php echo $ads_homepage; ?></div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Category sections (single optimized query) -->
        <?php foreach (array_slice($categories, 0, 3) as $cat): 
            $cat_posts = $posts_by_category[$cat['id']] ?? [];
            if (empty($cat_posts)) continue;
        ?>
        <section class="section category-section">
            <h2 class="section-heading"><a href="<?php echo base_url('category.php?slug=' . e($cat['slug'])); ?>"><?php echo e($cat['name']); ?></a></h2>
            <div class="posts-grid three-col">
                <?php foreach ($cat_posts as $post): ?>
                <article class="post-card">
                    <a href="<?php echo base_url('post.php?slug=' . e($post['slug'])); ?>" class="post-card-img">
                        <?php if (!empty($post['image'])): ?>
                        <img src="<?php echo base_url($post['image']); ?>" alt="<?php echo e($post['title']); ?>" loading="lazy">
                        <?php else: ?>
                        <div class="img-placeholder"></div>
                        <?php endif; ?>
                    </a>
                    <div class="post-card-body">
                        <?php if (!empty($post['is_sponsored'])): ?><span class="sponsored-badge">Sponsored</span><?php endif; ?>
                        <h3><a href="<?php echo base_url('post.php?slug=' . e($post['slug'])); ?>"><?php echo e($post['title']); ?></a></h3>
                        <?php if (!empty($post['is_event'])): ?>
                        <?php $status = event_effective_status($post); ?>
                        <div class="event-inline-meta">
                            <span class="event-status-badge event-status-<?php echo e($status); ?>"><?php echo e(event_status_label($status)); ?></span>
                        </div>
                        <?php endif; ?>
                        <span class="meta"><?php echo format_date($post['created_at']); ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
