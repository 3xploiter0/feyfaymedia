<?php
/**
 * Admin - Add new post - FeyFay Media
 * Title, slug, summary, content, image, category, tags, featured, status, meta
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin();
$admin_title = 'Add Event Update';
$user = current_user($pdo);
$categories = get_categories($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors[] = 'Invalid request. Please try again.'; }
    else {
    $title = sanitize_plain_text($_POST['title'] ?? '', 255);
    $summary = sanitize_plain_text($_POST['summary'] ?? '', 600);
    $content = sanitize_post_content($_POST['content'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $publish_mode = $_POST['publish_mode'] ?? 'draft'; // 'now' | 'draft' | 'schedule'
    $scheduled_at = trim($_POST['scheduled_at'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_sponsored = isset($_POST['is_sponsored']) ? 1 : 0;
    $is_event = isset($_POST['is_event']) ? 1 : 0;
    $event_type = sanitize_plain_text($_POST['event_type'] ?? '', 80);
    $event_city = sanitize_plain_text($_POST['event_city'] ?? '', 120);
    $event_location = sanitize_plain_text($_POST['event_location'] ?? '', 255);
    $event_start_at_input = trim((string)($_POST['event_start_at'] ?? ''));
    $event_end_at_input = trim((string)($_POST['event_end_at'] ?? ''));
    $event_status = strtolower(trim((string)($_POST['event_status'] ?? '')));
    if (!in_array($event_status, ['upcoming', 'ongoing', 'completed', 'cancelled'], true)) {
        $event_status = 'upcoming';
    }
    $meta_title = sanitize_plain_text($_POST['meta_title'] ?? '', 255);
    $meta_description = sanitize_plain_text($_POST['meta_description'] ?? '', 320);
    $tags_input = sanitize_plain_text($_POST['tags'] ?? '', 400);

    if (!$title) $errors[] = 'Title is required.';
    if ($title !== '' && strlen($title) < 3) $errors[] = 'Title must be at least 3 characters.';
    if (!$category_id) $errors[] = 'Select a category.';
    if ($content === '') $errors[] = 'Content is required.';
    if ($publish_mode === 'schedule' && $scheduled_at === '') $errors[] = 'Please choose a date and time for scheduling.';
    if ($publish_mode === 'schedule' && $scheduled_at !== '' && (strtotime($scheduled_at) === false || strtotime($scheduled_at) <= time())) $errors[] = 'Scheduled time must be in the future.';

    $event_start_at = null;
    $event_end_at = null;
    if ($is_event) {
        if ($event_start_at_input === '') {
            $errors[] = 'Event start date and time is required.';
        } else {
            $start_ts = strtotime($event_start_at_input);
            if ($start_ts === false) {
                $errors[] = 'Invalid event start date.';
            } else {
                $event_start_at = date('Y-m-d H:i:s', $start_ts);
            }
        }
        if ($event_end_at_input !== '') {
            $end_ts = strtotime($event_end_at_input);
            if ($end_ts === false) {
                $errors[] = 'Invalid event end date.';
            } else {
                $event_end_at = date('Y-m-d H:i:s', $end_ts);
            }
        }
        if ($event_start_at && $event_end_at && strtotime($event_end_at) < strtotime($event_start_at)) {
            $errors[] = 'Event end time must be after start time.';
        }
    } else {
        $event_type = '';
        $event_city = '';
        $event_location = '';
        $event_status = '';
    }

    $status = 'draft';
    $published_at = null;
    if ($publish_mode === 'now') {
        $status = 'published';
        $published_at = null; // visible immediately
    } elseif ($publish_mode === 'schedule' && $scheduled_at !== '') {
        $status = 'published';
        $published_at = date('Y-m-d H:i:s', strtotime($scheduled_at));
    }

    if (empty($errors)) {
        $slug = slugify($title);
        $n = 0;
        while (true) {
            $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) break;
            $slug = slugify($title) . '-' . (++$n);
        }

        $image_path = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['image']['tmp_name']);
            if (in_array($mime, $allowed)) {
                $ext = mime_to_ext($mime);
                $filename = 'post-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                ensure_upload_dir();
                $full_path = rtrim(UPLOAD_PATH, '/') . '/' . $filename;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                    $image_path = rtrim(UPLOAD_URL, '/') . '/' . $filename;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO posts (title, slug, summary, content, image, category_id, author_id, status, published_at, is_featured, is_sponsored, is_event, event_type, event_city, event_location, event_start_at, event_end_at, event_status, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $title, $slug, $summary, $content, $image_path, $category_id, $user['id'], $status, $published_at, $is_featured, $is_sponsored,
            $is_event, $event_type ?: null, $event_city ?: null, $event_location ?: null, $event_start_at, $event_end_at, $event_status ?: null,
            $meta_title ?: null, $meta_description ?: null
        ]);

        $post_id = (int) $pdo->lastInsertId();
        // Tags: comma-separated names, create tag if not exists, link in post_tags
        if ($tags_input !== '') {
            $tag_names = array_unique(array_map('trim', explode(',', $tags_input)));
            foreach ($tag_names as $name) {
                $name = trim($name);
                if ($name === '') continue;
                $tag_slug = slugify($name);
                $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
                $stmt->execute([$tag_slug]);
                $tag = $stmt->fetch();
                if (!$tag) {
                    $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)")->execute([$name, $tag_slug]);
                    $tag_id = (int) $pdo->lastInsertId();
                } else {
                    $tag_id = (int) $tag['id'];
                }
                $pdo->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$post_id, $tag_id]);
            }
        }
        toast_add_flash('success', 'Event update created successfully.');
        redirect(base_url('admin/posts.php'));
    }
    }
}

foreach ($errors as $e) {
    toast_add('error', $e);
}

$load_tinymce = true;
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content">
    <h1>Add Event Update</h1>
    <form method="post" enctype="multipart/form-data" class="admin-form post-form">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required value="<?php echo e($title ?? ($_POST['title'] ?? '')); ?>">
        </div>
        <div class="form-group">
            <label for="summary">Summary</label>
            <textarea id="summary" name="summary" rows="3"><?php echo e($summary ?? ($_POST['summary'] ?? '')); ?></textarea>
        </div>
        <div class="form-group">
            <label for="content">Content *</label>
            <textarea id="content" name="content" rows="14"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category *</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Publish</label>
                <div class="publish-mode">
                    <label><input type="radio" name="publish_mode" value="now" <?php echo ($_POST['publish_mode'] ?? '') === 'now' ? 'checked' : ''; ?>> Publish Now</label>
                    <label><input type="radio" name="publish_mode" value="draft" <?php echo ($_POST['publish_mode'] ?? 'draft') === 'draft' ? 'checked' : ''; ?>> Save as Draft</label>
                    <label><input type="radio" name="publish_mode" value="schedule" id="publish_mode_schedule" <?php echo ($_POST['publish_mode'] ?? '') === 'schedule' ? 'checked' : ''; ?>> Schedule for Later</label>
                </div>
                <div class="form-group schedule-datetime" id="scheduleDatetimeWrap" style="display:<?php echo ($_POST['publish_mode'] ?? '') === 'schedule' ? 'block' : 'none'; ?>; margin-top: 0.5rem;">
                    <label for="scheduled_at">Date &amp; time</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?php echo e($_POST['scheduled_at'] ?? ''); ?>" min="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_featured" value="1" <?php echo !empty($_POST['is_featured']) ? 'checked' : ''; ?>> Featured</label>
                <label><input type="checkbox" name="is_sponsored" value="1" <?php echo !empty($_POST['is_sponsored']) ? 'checked' : ''; ?>> Sponsored</label>
                <label><input type="checkbox" name="is_event" value="1" <?php echo !empty($_POST['is_event']) ? 'checked' : ''; ?>> This is an event</label>
            </div>
        </div>
        <div id="eventFieldsWrap" style="display:<?php echo !empty($_POST['is_event']) ? 'block' : 'none'; ?>;">
            <h2>Event Details</h2>
            <div class="form-row">
                <div class="form-group">
                    <label for="event_type">Event Type</label>
                    <input type="text" id="event_type" name="event_type" placeholder="Forum, Workshop, Meetup..." value="<?php echo e($_POST['event_type'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="event_status">Event Status</label>
                    <select id="event_status" name="event_status">
                        <option value="upcoming" <?php echo (($_POST['event_status'] ?? 'upcoming') === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="ongoing" <?php echo (($_POST['event_status'] ?? '') === 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="completed" <?php echo (($_POST['event_status'] ?? '') === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo (($_POST['event_status'] ?? '') === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="event_city">City</label>
                    <input type="text" id="event_city" name="event_city" placeholder="Dar es Salaam" value="<?php echo e($_POST['event_city'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="event_location">Location / Venue</label>
                    <input type="text" id="event_location" name="event_location" placeholder="Mlimani City Conference Hall" value="<?php echo e($_POST['event_location'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="event_start_at">Event Start *</label>
                    <input type="datetime-local" id="event_start_at" name="event_start_at" value="<?php echo e($_POST['event_start_at'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="event_end_at">Event End</label>
                    <input type="datetime-local" id="event_end_at" name="event_end_at" value="<?php echo e($_POST['event_end_at'] ?? ''); ?>">
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="tags">Tags (comma-separated)</label>
            <input type="text" id="tags" name="tags" placeholder="events, networking, business" value="<?php echo e($_POST['tags'] ?? ''); ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="meta_title">Meta Title (SEO)</label>
                <input type="text" id="meta_title" name="meta_title" value="<?php echo e($meta_title ?? ($_POST['meta_title'] ?? '')); ?>">
            </div>
            <div class="form-group">
                <label for="meta_description">Meta Description (SEO)</label>
                <input type="text" id="meta_description" name="meta_description" value="<?php echo e($meta_description ?? ($_POST['meta_description'] ?? '')); ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="image">Featured Image</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
        </div>
        <button type="submit" class="btn btn-primary">Save Event Update</button>
    </form>
</div>
<script>
(function() {
    var schedule = document.getElementById('publish_mode_schedule');
    var wrap = document.getElementById('scheduleDatetimeWrap');
    var eventToggle = document.querySelector('input[name="is_event"]');
    var eventWrap = document.getElementById('eventFieldsWrap');
    if (!schedule || !wrap) return;
    document.querySelectorAll('input[name="publish_mode"]').forEach(function(r) {
        r.addEventListener('change', function() { wrap.style.display = document.getElementById('publish_mode_schedule').checked ? 'block' : 'none'; });
    });
    if (eventToggle && eventWrap) {
        eventToggle.addEventListener('change', function() {
            eventWrap.style.display = eventToggle.checked ? 'block' : 'none';
        });
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
