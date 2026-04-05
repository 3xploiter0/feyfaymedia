<?php
/**
 * Admin - List all posts - FeyFay Media
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$admin_title = 'Event Updates';

$stmt = $pdo->query("SELECT p.*, c.name AS category_name, u.name AS author_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN users u ON p.author_id = u.id ORDER BY p.updated_at DESC");
$posts = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Event Updates</h1>
    <p><a href="<?php echo base_url('admin/add-post.php'); ?>" class="btn btn-primary">Add Event Update</a></p>
    <?php if (empty($posts)): ?>
    <p>No event updates yet.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Event</th>
                <th>Start</th>
                <th>Author</th>
                <th>Status</th>
                <th>Featured</th>
                <th>Sponsored</th>
                <th>Views</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $p): ?>
            <tr>
                <td><?php echo e($p['title']); ?></td>
                <td><?php echo e($p['category_name'] ?? '-'); ?></td>
                <td>
                    <?php if (!empty($p['is_event'])): ?>
                        <?php $ev_status = event_effective_status($p); ?>
                        <strong><?php echo e($p['event_type'] ?: 'Event'); ?></strong><br>
                        <small><?php echo e($p['event_city'] ?: 'City TBA'); ?> · <?php echo e(event_status_label($ev_status)); ?></small>
                    <?php else: ?>
                        <small>Update only</small>
                    <?php endif; ?>
                </td>
                <td><?php echo !empty($p['event_start_at']) ? e(format_event_datetime($p['event_start_at'])) : '-'; ?></td>
                <td><?php echo e($p['author_name'] ?? '-'); ?></td>
                <td><?php
                    $disp = post_display_status($p);
                    echo '<span class="status-badge status-' . e($disp) . '">' . e($disp) . '</span>';
                    if ($disp === 'scheduled' && !empty($p['published_at'])) echo ' <small title="Scheduled for">' . format_datetime($p['published_at']) . '</small>';
                ?></td>
                <td><?php echo !empty($p['is_featured']) ? 'Yes' : 'No'; ?></td>
                <td><?php echo !empty($p['is_sponsored']) ? 'Yes' : 'No'; ?></td>
                <td><?php echo (int)$p['views']; ?></td>
                <td><?php echo format_date($p['created_at']); ?></td>
                <td class="actions">
                    <?php if ($disp === 'published'): ?><a href="<?php echo base_url('post.php?slug=' . e($p['slug'])); ?>" target="_blank">View</a> <?php endif; ?>
                    <a href="<?php echo base_url('admin/edit-post.php?id=' . $p['id']); ?>">Edit</a>
                    <?php if (can_delete_posts()): ?><a href="<?php echo base_url('admin/delete-post.php?id=' . $p['id']); ?>" class="link-delete">Delete</a><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
