<?php
/**
 * AJAX: Submit comment - FeyFay Media
 * POST: post_id, name, email, message
 * Returns JSON: { success, message }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$ip = client_ip();
if (!csrf_verify()) {
    echo json_encode(['success' => false, 'message' => 'Security validation failed. Refresh and try again.']);
    exit;
}
if (!empty($_POST['website'])) {
    echo json_encode(['success' => true, 'message' => 'Comment submitted. It will appear after approval.']);
    exit;
}
if (rate_limit_too_many('comment:' . $ip, 6, 600)) {
    echo json_encode(['success' => false, 'message' => 'Too many comments submitted. Please wait a few minutes.']);
    exit;
}

$post_id = (int)($_POST['post_id'] ?? 0);
$name = sanitize_plain_text($_POST['name'] ?? '', 100);
$email = strtolower(trim($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $message);
if (function_exists('mb_strlen')) {
    if (mb_strlen($message, 'UTF-8') > 2000) $message = mb_substr($message, 0, 2000, 'UTF-8');
} else {
    if (strlen($message) > 2000) $message = substr($message, 0, 2000);
}

if (!$post_id || !$name || !$email || !$message) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email.']);
    exit;
}

// Verify post exists and is visible on public site
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND " . posts_public_visibility_sql('posts'));
$stmt->execute([$post_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Event update not found.']);
    exit;
}

rate_limit_hit('comment:' . $ip, 600);
$stmt = $pdo->prepare("INSERT INTO comments (post_id, name, email, message, status) VALUES (?, ?, ?, ?, 'pending')");
$stmt->execute([$post_id, $name, $email, $message]);
echo json_encode(['success' => true, 'message' => 'Comment submitted. It will appear after approval.']);
