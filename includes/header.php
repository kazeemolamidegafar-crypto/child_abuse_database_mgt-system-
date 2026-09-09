<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= isset($pageTitle) ? h($pageTitle) . ' · ' : '' ?>Child Protection CMS</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<?php if (is_logged_in()): ?>
<nav class="navbar">
    <div class="brand">Child Protection CMS</div>
    <div class="nav-links">
        <?php if (current_role() === 'administrator'): ?>
            <a href="/admin/dashboard.php">Overview</a>
            <a href="/admin/users.php">Users</a>
            <a href="/caseworker/dashboard.php">All Cases</a>
            <a href="/admin/audit_log.php">Audit Log</a>
            <a href="/admin/reports.php">Reports</a>
        <?php elseif (current_role() === 'caseworker'): ?>
            <a href="/caseworker/dashboard.php">My Cases</a>
        <?php elseif (current_role() === 'intake_officer'): ?>
            <a href="/intake/dashboard.php">Register Case</a>
        <?php endif; ?>
    </div>
    <div class="nav-user">
        <span><?= h($_SESSION['full_name']) ?> <em>(<?= h(str_replace('_', ' ', current_role())) ?>)</em></span>
        <a href="/logout.php" class="btn-logout">Log out</a>
    </div>
</nav>
<?php endif; ?>
<main class="container">
<?php if (!empty($_SESSION['flash'])): ?>
    <?php foreach ($_SESSION['flash'] as $flash): ?>
        <div class="flash flash-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endforeach; ?>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
