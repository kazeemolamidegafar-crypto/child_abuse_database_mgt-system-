<?php
/**
 * seed.php
 *
 * Command-line setup script: applies schema.sql if needed, then creates
 * demo accounts (one per role) so the system can be explored immediately.
 *
 * Run once from the project root:
 *   php seed.php
 *
 * IMPORTANT: these are demo credentials for local evaluation only.
 * Change every password (and remove/replace demo data) before any real use.
 */

require_once __DIR__ . '/config.php';

if (php_sapi_name() !== 'cli') {
    die("Run this script from the command line: php seed.php\n");
}

$pdo = db();

echo "Applying schema...\n";
$sql = file_get_contents(__DIR__ . '/schema.sql');
foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    if ($statement === '' || str_starts_with($statement, '--') || str_starts_with($statement, 'SET')) {
        continue;
    }
    $pdo->exec($statement);
}

$demoUsers = [
    ['System Administrator', 'admin',       'Admin@12345',  'administrator'],
    ['Blessing Adeyemi',     'caseworker1', 'Case@12345',   'caseworker'],
    ['Tunde Bakare',         'intake1',     'Intake@12345', 'intake_officer'],
];

foreach ($demoUsers as [$fullName, $username, $password, $role]) {
    $exists = $pdo->prepare('SELECT 1 FROM users WHERE username = :u');
    $exists->execute(['u' => $username]);
    if ($exists->fetch()) {
        continue;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, username, password_hash, role) VALUES (:name, :user, :hash, :role)'
    );
    $stmt->execute([
        'name' => $fullName,
        'user' => $username,
        'hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
    ]);
}

echo "Database ready.\n";
echo "Demo accounts (change these passwords before real use):\n";
foreach ($demoUsers as [$fullName, $username, $password, $role]) {
    printf("  %-15s | username: %-12s | password: %s\n", $role, $username, $password);
}
