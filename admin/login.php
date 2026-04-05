<?php
/**
 * Admin login - FeyFay Media
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(base_url('admin/dashboard.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_plain_text($_POST['username'] ?? '', 80);
    $password = (string)($_POST['password'] ?? '');
    $ip = client_ip();
    $ip_key = 'login:ip:' . $ip;
    $user_key = 'login:user:' . $ip . ':' . strtolower($username ?: 'unknown');

    if (rate_limit_too_many($ip_key, 20, 900) || rate_limit_too_many($user_key, 8, 900)) {
        $error = 'Too many login attempts. Please wait 15 minutes and try again.';
    } elseif (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
        if (!$username || !$password) {
            rate_limit_hit($ip_key, 900);
            rate_limit_hit($user_key, 900);
            $error = 'Please enter username and password.';
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password, role, is_active FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && (int)($user['is_active'] ?? 1) === 1 && password_verify($password, $user['password'])) {
                rate_limit_clear($ip_key);
                rate_limit_clear($user_key);
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'staff';
                $redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : 'dashboard.php';
                $redirect = preg_replace('#^admin/#', '', $redirect);
                if (!preg_match('#^[a-z0-9_\-\.]+\.php(\?.*)?$#i', $redirect)) $redirect = 'dashboard.php';
                redirect(base_url('admin/' . $redirect));
            }
            rate_limit_hit($ip_key, 900);
            rate_limit_hit($user_key, 900);
            $error = 'Invalid username or password.';
        }
    }
}
$settings = get_settings($pdo);
$site_name = e($settings['site_name'] ?? DEFAULT_SITE_NAME);
if ($error !== '') {
    toast_add('error', $error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo $site_name; ?> Admin</title>
    <link rel="icon" href="<?php echo base_url('assets/images/feyfaylogo.png'); ?>" type="image/png">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/admin.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body admin-login-page">
    <div class="login-box">
        <h1><?php echo $site_name; ?></h1>
        <p class="login-subtitle">Admin Login</p>
        <form method="post" action="" class="admin-form">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus value="<?php echo e($username ?? ($_POST['username'] ?? '')); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Log In</button>
        </form>
        <p class="login-footer"><a href="<?php echo base_url(); ?>">&larr; Back to site</a></p>
    </div>
    <script src="<?php echo base_url('assets/js/admin.js'); ?>"></script>
    <?php echo render_toasts_script(); ?>
</body>
</html>
