<?php
/**
 * config.php
 * Loads environment configuration, opens the PDO/MySQL connection,
 * and starts a hardened session. Included at the top of every page.
 */

// ---------------------------------------------------------------------
// Load .env (very small parser -- no external dependency required)
// ---------------------------------------------------------------------
ini_set('display_errors', '1');
error_reporting(E_ALL);
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        putenv(trim($key) . '=' . trim($value));
    }
}

function env(string $key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// ---------------------------------------------------------------------
// Database connection
// ---------------------------------------------------------------------
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'child_protection'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ---------------------------------------------------------------------
// Session hardening + idle timeout
// ---------------------------------------------------------------------
$timeoutMinutes = (int) env('SESSION_TIMEOUT_MINUTES', 30);

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', '1');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce idle timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeoutMinutes * 60) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/flash.php';
