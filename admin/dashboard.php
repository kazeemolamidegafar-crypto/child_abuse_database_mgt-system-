<?php
require_once __DIR__ . '/../config.php';
require_role('administrator');

$statusCounts = db()->query('SELECT status, COUNT(*) AS n FROM cases GROUP BY status')->fetchAll();
$totalCases = (int) db()->query('SELECT COUNT(*) AS n FROM cases')->fetch()['n'];
$totalChildren = (int) db()->query('SELECT COUNT(*) AS n FROM children')->fetch()['n'];
$recentLogs = db()->query('SELECT * FROM audit_logs ORDER BY log_id DESC LIMIT 10')->fetchAll();

$pageTitle = 'Administrator Overview';
include __DIR__ . '/../includes/header.php';
?>
<h1>Administrator Overview</h1>

<div class="stat-row">
    <div class="stat-card"><span class="stat-num"><?= $totalCases ?></span><span>Total Cases</span></div>
    <div class="stat-card"><span class="stat-num"><?= $totalChildren ?></span><span>Child Records</span></div>
    <?php foreach ($statusCounts as $row): ?>
        <div class="stat-card"><span class="stat-num"><?= (int) $row['n'] ?></span><span><?= h($row['status']) ?></span></div>
    <?php endforeach; ?>
</div>

<h2>Recent Audit Activity</h2>
<table class="data-table">
    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Target</th><th>Details</th></tr></thead>
    <tbody>
    <?php if ($recentLogs): ?>
        <?php foreach ($recentLogs as $log): ?>
            <tr>
                <td><?= h($log['timestamp']) ?></td>
                <td><?= h($log['username'] ?? '—') ?></td>
                <td><?= h($log['action']) ?></td>
                <td><?= h(trim(($log['target_entity'] ?? '') . ' ' . ($log['target_id'] ?? ''))) ?></td>
                <td><?= h($log['details'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="5">No activity recorded yet.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<p><a href="/admin/audit_log.php">View full audit trail &rarr;</a></p>

<?php include __DIR__ . '/../includes/footer.php'; ?>
