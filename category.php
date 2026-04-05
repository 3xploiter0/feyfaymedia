<?php
/**
 * Category archive - FeyFay Media
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if ($slug === '') {
    redirect(base_url());
}

$category = get_category($pdo, $slug);
if (!$category) {
    include __DIR__ . '/404.php';
    exit;
}

$page_title = $category['name'];
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = POSTS_PER_PAGE;
$offset = ($page - 1) * $per_page;
$total = count_posts($pdo, $category['id']);
$total_pages = max(1, (int)ceil($total / $per_page));
$posts = get_posts($pdo, $per_page, $offset, $category['id']);
$base_url = 'category.php?slug=' . urlencode($category['slug']) . '&page=';
$current_page = $page;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container content-wrap page-shell">
    <div class="content-main">
        <section class="page-intro">
            <p class="section-kicker">Category</p>
            <h1 class="page-title"><?php echo e($category['name']); ?></h1>
            <p class="page-description">Browse the latest posts and updates from this section.</p>
        </section>
        <section class="section">
            <?php if (empty($posts)): ?>
            <p class="no-posts">No articles in this category yet.</p>
            <?php else: ?>
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
                        <p class="excerpt"><?php echo e(excerpt($post['summary'] ?: $post['content'], 120)); ?></p>
                        <span class="meta"><?php echo format_date($post['created_at']); ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php include __DIR__ . '/includes/pagination.php'; ?>
            <?php endif; ?>
        </section>
    </div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
