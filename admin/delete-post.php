<?php
/**
 * Admin - Delete post (admin only) - FeyFay Media
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
if (!can_delete_posts()) {
    header('Location: ' . base_url('admin/posts.php'));
    exit;
}

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) {
    header('Location: ' . base_url('admin/posts.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
    toast_add_flash('success', 'Post deleted.');
    header('Location: ' . base_url('admin/posts.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT title FROM posts WHERE id = ?");
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) {
    header('Location: ' . base_url('admin/posts.php'));
    exit;
}

$admin_title = 'Delete Event Update';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Delete Event Update</h1>
    <p>Are you sure you want to delete <strong><?php echo e($post['title']); ?></strong>? This cannot be undone.</p>
    <form method="post" class="admin-form">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="form-actions">
            <button type="submit" class="btn btn-danger">Delete</button>
            <a href="<?php echo base_url('admin/posts.php'); ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
