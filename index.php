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
$latest = get_posts($pdo, 8, $featured ? 1 : 0, null);
$categories = get_categories($pdo);
$category_ids = array_slice(array_column($categories, 'id'), 0, 4);
$posts_by_category = get_posts_by_category_ids($pdo, $category_ids, 3);
$settings = get_settings($pdo);
$ads_homepage = $settings['ads_homepage'] ?? '';
$site_description = trim($settings['site_description'] ?? DEFAULT_SITE_DESCRIPTION);
$hero_spotlight_count = count($latest) > 4 ? 2 : (count($latest) > 2 ? 1 : 0);
$hero_spotlight = array_slice($latest, 0, $hero_spotlight_count);
$latest_grid = array_slice($latest, $hero_spotlight_count);
if (empty($latest_grid) && !empty($hero_spotlight)) {
    $latest_grid = $hero_spotlight;
    $hero_spotlight = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breaking news ticker -->
<?php if (!empty($breaking)): ?>
<div class="breaking-bar">
    <div class="container">
        <span class="breaking-label">Breaking</span>
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
        <div class="hero-head">
            <div>
                <p class="section-kicker">Top story</p>
                <h1 class="hero-heading">Featured post</h1>
            </div>
            <p class="hero-support"><?php echo e($site_description); ?></p>
        </div>
        <div class="hero-layout">
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
                    <h2 class="featured-title"><a href="<?php echo base_url('post.php?slug=' . e($featured['slug'])); ?>"><?php echo e($featured['title']); ?></a></h2>
                    <p class="featured-excerpt"><?php echo e(excerpt($featured['summary'] ?: $featured['content'], 220)); ?></p>
                    <div class="featured-meta"><?php echo format_date($featured['created_at']); ?> &middot; <?php echo e($featured['author_name']); ?></div>
                    <a href="<?php echo base_url('post.php?slug=' . e($featured['slug'])); ?>" class="btn btn-primary">Read article</a>
                </div>
            </article>
            <aside class="hero-rail">
                <div class="hero-rail-card">
                    <p class="hero-rail-label">Sections</p>
                    <div class="topic-list">
                        <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                        <a href="<?php echo base_url('category.php?slug=' . e($cat['slug'])); ?>" class="topic-pill"><?php echo e($cat['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php foreach ($hero_spotlight as $post): ?>
                <article class="hero-mini-card">
                    <a href="<?php echo base_url('category.php?slug=' . e($post['category_slug'])); ?>" class="cat-badge"><?php echo e($post['category_name']); ?></a>
                    <h2 class="hero-mini-title"><a href="<?php echo base_url('post.php?slug=' . e($post['slug'])); ?>"><?php echo e($post['title']); ?></a></h2>
                    <p class="hero-mini-excerpt"><?php echo e(excerpt($post['summary'] ?: $post['content'], 90)); ?></p>
                    <span class="meta"><?php echo format_date($post['created_at']); ?></span>
                </article>
                <?php endforeach; ?>
            </aside>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="container content-wrap home-layout">
    <div class="content-main">
        <!-- Latest news grid -->
        <section class="section latest-section">
            <div class="section-heading-row">
                <div>
                    <p class="section-kicker">Latest posts</p>
                    <h2 class="section-heading">Recent stories</h2>
                </div>
                <p class="section-description">Freshly published stories, commentary, and updates from across the site.</p>
            </div>
            <?php if (empty($latest_grid) && !$featured): ?>
            <p class="no-posts">No articles yet. Check back soon.</p>
            <?php elseif (!empty($latest_grid)): ?>
            <div class="posts-grid latest-grid">
                <?php foreach ($latest_grid as $index => $post): ?>
                <article class="post-card <?php echo ($index === 0 && count($latest_grid) >= 3) ? 'post-card-featured' : ''; ?>">
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
        <?php foreach (array_slice($categories, 0, 4) as $cat): 
            $cat_posts = $posts_by_category[$cat['id']] ?? [];
            if (empty($cat_posts)) continue;
        ?>
        <section class="section category-section">
            <div class="section-heading-row">
                <div>
                    <p class="section-kicker">Category</p>
                    <h2 class="section-heading"><a href="<?php echo base_url('category.php?slug=' . e($cat['slug'])); ?>"><?php echo e($cat['name']); ?></a></h2>
                </div>
                <p class="section-description">Selected posts from the <?php echo e($cat['name']); ?> section.</p>
            </div>
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
