<?php
/**
 * Search - FeyFay Media (paginated)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page_title = $q !== '' ? 'Search: ' . e($q) : 'Search';
$posts = [];
$total = 0;
$total_pages = 1;
$current_page = 1;
$base_url_search = '';

if ($q !== '') {
    $total = count_search_posts($pdo, $q);
    $per_page = POSTS_PER_PAGE;
    $total_pages = max(1, (int) ceil($total / $per_page));
    $current_page = max(1, min((int) ($_GET['page'] ?? 1), $total_pages));
    $offset = ($current_page - 1) * $per_page;
    $posts = search_posts($pdo, $q, $per_page, $offset);
    $base_url_search = 'search.php?q=' . urlencode($q) . '&page=';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container content-wrap">
    <div class="content-main">
        <section class="section search-page">
            <h1 class="page-title">Search</h1>
            <form class="search-form" action="<?php echo base_url('search.php'); ?>" method="get">
                <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Search event updates..." required>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
            <?php if ($q === ''): ?>
            <p class="search-hint">Enter a keyword above.</p>
            <?php elseif (empty($posts)): ?>
            <p class="no-posts">No results for "<?php echo e($q); ?>".</p>
            <?php else: ?>
            <p class="results-count"><?php echo $total; ?> result(s)</p>
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <a href="<?php echo base_url('post.php?slug=' . e($post['slug'])); ?>" class="post-card-img">
                        <?php if (!empty($post['image'])): ?>
                        <img src="<?php echo base_url($post['image']); ?>" alt="<?php echo e($post['title']); ?>" loading="lazy">
                        <?php else: ?>
                        <div class="img-placeholder"></div>
                        <?php endif; ?>
                    </a>
                    <div class="post-card-body">
                        <a href="<?php echo base_url('category.php?slug=' . e($post['category_slug'])); ?>" class="cat-badge"><?php echo e($post['category_name']); ?></a>
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
            <?php
            $base_url = $base_url_search;
            include __DIR__ . '/includes/pagination.php';
            ?>
            <?php endif; ?>
        </section>
    </div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
