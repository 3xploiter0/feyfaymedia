<?php
/**
 * Admin authentication & authorization - FeyFay Media
 * Roles: staff (post/edit, moderate comments) | admin (full access, manage staff/settings/ads)
 */

/**
 * Check if current user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login - redirect to admin login if not authenticated
 */
function require_admin() {
    if (!is_logged_in()) {
        $redirect = 'login.php';
        if (isset($_SERVER['REQUEST_URI'])) {
            $redirect .= '?redirect=' . urlencode($_SERVER['REQUEST_URI']);
        }
        header('Location: ' . base_url('admin/' . $redirect));
        exit;
    }
    $user = current_user(null);
    if (!$user || empty($user['is_active'])) {
        session_destroy();
        header('Location: ' . base_url('admin/login.php'));
        exit;
    }
}

/**
 * Require admin role - redirect to dashboard if not admin (for settings, users, categories manage, post delete)
 */
function require_role_admin() {
    require_admin();
    $user = current_user(null);
    if (empty($user) || ($user['role'] ?? '') !== 'admin') {
        header('Location: ' . base_url('admin/dashboard.php'));
        exit;
    }
}

/**
 * Get current logged-in user (id, name, username, email, role, is_active)
 */
function current_user($pdo = null) {
    if (!is_logged_in()) return null;
    if ($pdo === null) {
        global $pdo;
    }
    if (empty($pdo)) return null;
    $stmt = $pdo->prepare("SELECT id, name, username, email, role, is_active FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * True if current user is admin (full privileges)
 */
function is_admin() {
    $u = current_user(null);
    return $u && ($u['role'] ?? '') === 'admin';
}

/**
 * Staff can post and edit; only admin can delete posts
 */
function can_delete_posts() {
    return is_admin();
}

/**
 * Only admin can change site settings and ad zones
 */
function can_manage_settings() {
    return is_admin();
}

/**
 * Only admin can add/edit/delete categories and manage staff
 */
function can_manage_categories() {
    return is_admin();
}

/**
 * Only admin can manage users (staff)
 */
function can_manage_users() {
    return is_admin();
}

// CSRF helpers are defined in includes/functions.php for both public and admin forms.
