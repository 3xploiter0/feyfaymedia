<?php
/**
 * Event listings with filters - FEYFAY INVESTMENT
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$raw_filters = [
    'q' => $_GET['q'] ?? '',
    'status' => $_GET['status'] ?? '',
    'city' => $_GET['city'] ?? '',
    'type' => $_GET['type'] ?? '',
    'category_id' => $_GET['category_id'] ?? 0,
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
];
$filters = normalize_event_filters($raw_filters);

$page_title = 'Events';
$meta_description = 'Browse upcoming, ongoing, and completed events with filters by city, type, and date.';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = POSTS_PER_PAGE;
$offset = ($page - 1) * $per_page;

$total = count_event_posts($pdo, $filters);
$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

$events = get_event_posts($pdo, $filters, $per_page, $offset);
$options = get_event_filter_options($pdo);
$categories = get_categories($pdo);

$query = $_GET;
unset($query['page']);
$base_url = 'events.php';
if (!empty($query)) {
    $base_url .= '?' . http_build_query($query) . '&page=';
} else {
    $base_url .= '?page=';
}
$current_page = $page;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container content-wrap">
    <div class="content-main">
        <section class="section events-page">
            <h1 class="page-title">Events</h1>
            <p class="events-intro">Find upcoming and ongoing opportunities by location, category, and event type.</p>

            <form class="event-filters" method="get" action="<?php echo base_url('events.php'); ?>">
                <div class="event-filters-grid">
                    <div class="form-group">
                        <label for="f_q">Keyword</label>
                        <input type="search" id="f_q" name="q" value="<?php echo e($filters['q']); ?>" placeholder="Search event title or venue">
                    </div>
                    <div class="form-group">
                        <label for="f_status">Status</label>
                        <select id="f_status" name="status">
                            <option value="">All statuses</option>
                            <option value="upcoming" <?php echo $filters['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="ongoing" <?php echo $filters['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="f_city">City</label>
                        <select id="f_city" name="city">
                            <option value="">All cities</option>
                            <?php foreach ($options['cities'] as $city): ?>
                            <option value="<?php echo e($city); ?>" <?php echo $filters['city'] === $city ? 'selected' : ''; ?>><?php echo e($city); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="f_type">Type</label>
                        <select id="f_type" name="type">
                            <option value="">All types</option>
                            <?php foreach ($options['types'] as $type): ?>
                            <option value="<?php echo e($type); ?>" <?php echo $filters['type'] === $type ? 'selected' : ''; ?>><?php echo e($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="f_category">Category</label>
                        <select id="f_category" name="category_id">
                            <option value="0">All categories</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?php echo (int)$c['id']; ?>" <?php echo (int)$filters['category_id'] === (int)$c['id'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="f_date_from">From</label>
                        <input type="date" id="f_date_from" name="date_from" value="<?php echo e($filters['date_from']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="f_date_to">To</label>
                        <input type="date" id="f_date_to" name="date_to" value="<?php echo e($filters['date_to']); ?>">
                    </div>
                </div>
                <div class="event-filters-actions">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo base_url('events.php'); ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <?php if (empty($events)): ?>
            <p class="no-posts">No events matched your filters.</p>
            <?php else: ?>
            <p class="results-count"><?php echo (int)$total; ?> event(s) found</p>
            <div class="posts-grid">
                <?php foreach ($events as $event): ?>
                <?php $status = event_effective_status($event); ?>
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
                            <span class="event-status-badge event-status-<?php echo e($status); ?>"><?php echo e(event_status_label($status)); ?></span>
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
            <?php include __DIR__ . '/includes/pagination.php'; ?>
            <?php endif; ?>
        </section>
    </div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
