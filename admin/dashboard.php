<?php
/**
 * Admin dashboard - FeyFay Media
 * Total posts, categories, comments; recent posts; most viewed
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();

$admin_title = 'Dashboard';

$total_posts = (int) $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$total_events = (int) $pdo->query("SELECT COUNT(*) FROM posts WHERE is_event = 1")->fetchColumn();
$event_status_expr = event_status_sql_expr('p');
$upcoming_events = (int) $pdo->query("SELECT COUNT(*) FROM posts p WHERE p.is_event = 1 AND " . posts_public_visibility_sql('p') . " AND ($event_status_expr = 'upcoming' OR $event_status_expr = 'ongoing')")->fetchColumn();
$total_categories = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_comments = (int) $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();

$stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.updated_at DESC LIMIT 10");
$recent_posts = $stmt->fetchAll();

$stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE " . posts_public_visibility_sql('p') . " ORDER BY p.views DESC LIMIT 10");
$most_viewed = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Dashboard</h1>
    <div class="dashboard-stats">
        <div class="stat-card">
            <span class="stat-number"><?php echo $total_posts; ?></span>
            <span class="stat-label">Total Event Updates</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $total_events; ?></span>
            <span class="stat-label">Total Events</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $upcoming_events; ?></span>
            <span class="stat-label">Upcoming / Ongoing</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $total_categories; ?></span>
            <span class="stat-label">Categories</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $total_comments; ?></span>
            <span class="stat-label">Comments</span>
        </div>
    </div>
    <div class="dashboard-actions">
        <a href="<?php echo base_url('admin/add-post.php'); ?>" class="btn btn-primary">Add Event Update</a>
        <a href="<?php echo base_url('admin/posts.php'); ?>" class="btn btn-secondary">Event Updates</a>
        <a href="<?php echo base_url('admin/comments.php'); ?>" class="btn btn-secondary">Comments</a>
        <?php if (can_manage_settings()): ?><a href="<?php echo base_url('admin/settings.php'); ?>" class="btn btn-secondary">Settings</a><?php endif; ?>
    </div>
    <div class="dashboard-grid">
        <section class="dashboard-section">
            <h2>Recent Event Updates</h2>
            <?php if (empty($recent_posts)): ?>
            <p>No event updates yet. <a href="<?php echo base_url('admin/add-post.php'); ?>">Create one</a>.</p>
            <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Title</th><th>Category</th><th>Event Start</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recent_posts as $p):
                    $disp = post_display_status($p);
                ?>
                <tr>
                    <td><?php echo e($p['title']); ?></td>
                    <td><?php echo e($p['category_name'] ?? '-'); ?></td>
                    <td><?php echo !empty($p['event_start_at']) ? e(format_event_datetime($p['event_start_at'])) : '-'; ?></td>
                    <td><span class="status-badge status-<?php echo e($disp); ?>"><?php echo e($disp); ?></span><?php if ($disp === 'scheduled' && !empty($p['published_at'])): ?> <small><?php echo format_datetime($p['published_at']); ?></small><?php endif; ?></td>
                    <td><?php echo format_datetime($p['updated_at']); ?></td>
                    <td><a href="<?php echo base_url('admin/edit-post.php?id=' . $p['id']); ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>
        <section class="dashboard-section">
            <h2>Most Viewed</h2>
            <?php if (empty($most_viewed)): ?>
            <p>No data yet.</p>
            <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Title</th><th>Views</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($most_viewed as $p): ?>
                <tr>
                    <td><?php echo e($p['title']); ?></td>
                    <td><?php echo (int)$p['views']; ?></td>
                    <td><a href="<?php echo base_url('admin/edit-post.php?id=' . $p['id']); ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
