<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    redirect('/index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE username = :u AND is_active = 1');
    $stmt->execute(['u' => $username]);
    $user = $stmt->fetch();

    // Generic error message on purpose: does not reveal whether the
    // username or password was incorrect (reduces the value of
    // credential-guessing attempts).
    if (!$user || !password_verify($password, $user['password_hash'])) {
        log_action(null, $username ?: null, 'LOGIN_FAILED', 'users', null, 'Invalid credentials');
        $error = 'Invalid username or password.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id']   = (int) $user['user_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['last_activity'] = time();

        log_action($user['user_id'], $user['username'], 'LOGIN_SUCCESS', 'users', $user['user_id']);
        redirect('/index.php');
    }
}

$pageTitle = 'Log in';
include __DIR__ . '/includes/header.php';
?>
<div class="login-wrap">
    <div class="login-card">
        <h1>Child Protection Case Management</h1>
        <p class="muted">Authorized personnel only. All access is logged.</p>
        <?php if ($error): ?>
            <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" action="/login.php">
            <?= csrf_field() ?>
            <label>Username</label>
            <input type="text" name="username" required autofocus>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit" class="btn-primary">Log In</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
