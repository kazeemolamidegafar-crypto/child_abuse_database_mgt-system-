<?php
require_once __DIR__ . '/../config.php';
require_role('administrator');

$qUser = trim($_GET['user'] ?? '');
$qFrom = trim($_GET['from'] ?? '');
$qTo   = trim($_GET['to'] ?? '');

$sql = 'SELECT * FROM audit_logs WHERE 1=1';
$params = [];

if ($qUser !== '') {
    $sql .= ' AND username LIKE :user';
    $params['user'] = '%' . $qUser . '%';
}
if ($qFrom !== '') {
    $sql .= ' AND timestamp >= :from';
    $params['from'] = $qFrom . ' 00:00:00';
}
if ($qTo !== '') {
    $sql .= ' AND timestamp <= :to';
    $params['to'] = $qTo . ' 23:59:59';
}
$sql .= ' ORDER BY log_id DESC LIMIT 500';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

log_action($_SESSION['user_id'], $_SESSION['username'], 'VIEW_AUDIT_LOG', 'audit_logs');

$pageTitle = 'Audit Trail';
include __DIR__ . '/../includes/header.php';
?>
<h1>Audit Trail</h1>

<form method="get" class="filter-bar">
    <input type="text" name="user" placeholder="Filter by username" value="<?= h($qUser) ?>">
    <input type="date" name="from" value="<?= h($qFrom) ?>">
    <input type="date" name="to" value="<?= h($qTo) ?>">
    <button type="submit" class="btn-small">Filter</button>
</form>

<table class="data-table">
    <thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Target</th><th>Details</th></tr></thead>
    <tbody>
    <?php if ($logs): ?>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= h($log['timestamp']) ?></td>
                <td><?= h($log['username'] ?? '—') ?></td>
                <td><?= h($log['action']) ?></td>
                <td><?= h(trim(($log['target_entity'] ?? '') . ' ' . ($log['target_id'] ?? ''))) ?></td>
                <td><?= h($log['details'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="5">No matching audit entries.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<p class="muted">Showing up to 500 most recent matching entries. This log is append-only; there is
no interface anywhere in the system to edit or delete an entry.</p>
<?php include __DIR__ . '/../includes/footer.php'; ?>
