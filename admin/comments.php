<?php
/**
 * Admin - Moderate comments - FeyFay Media
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$admin_title = 'Comments';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    if (isset($_POST['approve'])) {
        $cid = (int) $_POST['approve'];
        if ($cid) $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([$cid]);
        toast_add_flash('success', 'Comment approved.');
        header('Location: comments.php');
        exit;
    }
    if (isset($_POST['delete'])) {
        $cid = (int) $_POST['delete'];
        if ($cid) $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$cid]);
        toast_add_flash('success', 'Comment deleted.');
        header('Location: comments.php');
        exit;
    }
}

$stmt = $pdo->query("SELECT c.*, p.title AS post_title, p.slug AS post_slug FROM comments c LEFT JOIN posts p ON c.post_id = p.id ORDER BY c.created_at DESC");
$comments = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Comments</h1>
    <?php if (empty($comments)): ?>
    <p>No comments yet.</p>
    <?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Event Update</th>
                <th>Author</th>
                <th>Comment</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($comments as $c): ?>
            <tr>
                <td><a href="<?php echo base_url('post.php?slug=' . e($c['post_slug'])); ?>" target="_blank"><?php echo e($c['post_title'] ?? 'N/A'); ?></a></td>
                <td><?php echo e($c['name']); ?> (<?php echo e($c['email']); ?>)</td>
                <td><?php echo e(excerpt($c['message'], 80)); ?></td>
                <td><span class="status-badge status-<?php echo e($c['status']); ?>"><?php echo e($c['status']); ?></span></td>
                <td><?php echo format_datetime($c['created_at']); ?></td>
                <td class="actions">
                    <?php if ($c['status'] === 'pending'): ?>
                    <form method="post" class="form-inline" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="approve" value="<?php echo $c['id']; ?>">
                        <button type="submit" class="btn-link">Approve</button>
                    </form>
                    <?php endif; ?>
                    <form method="post" class="form-inline" style="display:inline;" onsubmit="return confirm('Delete this comment?');">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="delete" value="<?php echo $c['id']; ?>">
                        <button type="submit" class="btn-link link-delete">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
