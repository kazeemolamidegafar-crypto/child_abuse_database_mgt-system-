<?php
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    log_action($_SESSION['user_id'], $_SESSION['username'], 'LOGOUT', 'users', $_SESSION['user_id']);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

session_start();
flash('info', 'You have been logged out.');
redirect('/login.php');
