<?php
require_once __DIR__ . '/../config.php';
require_role('administrator');

$byStatus = db()->query('SELECT status, COUNT(*) AS n FROM cases GROUP BY status')->fetchAll();
$byCategory = db()->query('SELECT category_of_concern, COUNT(*) AS n FROM cases GROUP BY category_of_concern')->fetchAll();
$byMonth = db()->query(
    "SELECT DATE_FORMAT(date_reported, '%Y-%m') AS month, COUNT(*) AS n
     FROM cases GROUP BY month ORDER BY month"
)->fetchAll();
$referralOutcomes = db()->query('SELECT referral_status, COUNT(*) AS n FROM referrals GROUP BY referral_status')->fetchAll();

log_action($_SESSION['user_id'], $_SESSION['username'], 'EXPORT_ANONYMIZED_REPORT', 'reports', null, 'Viewed aggregate statistical report');

$pageTitle = 'Anonymized Reports';
include __DIR__ . '/../includes/header.php';
?>
<h1>Anonymized Statistical Reports</h1>
<p class="muted">These figures are aggregate counts only. No individually identifying information is displayed on this page.</p>

<div class="two-col">
    <div class="form-card">
        <h2>Cases by Status</h2>
        <table class="data-table">
            <thead><tr><th>Status</th><th>Count</th></tr></thead>
            <tbody>
            <?php foreach ($byStatus as $r): ?>
                <tr><td><?= h($r['status']) ?></td><td><?= (int) $r['n'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="form-card">
        <h2>Cases by Category of Concern</h2>
        <table class="data-table">
            <thead><tr><th>Category</th><th>Count</th></tr></thead>
            <tbody>
            <?php foreach ($byCategory as $r): ?>
                <tr><td><?= h($r['category_of_concern']) ?></td><td><?= (int) $r['n'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="two-col">
    <div class="form-card">
        <h2>Cases Reported by Month</h2>
        <table class="data-table">
            <thead><tr><th>Month</th><th>Count</th></tr></thead>
            <tbody>
            <?php foreach ($byMonth as $r): ?>
                <tr><td><?= h($r['month']) ?></td><td><?= (int) $r['n'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="form-card">
        <h2>Referral Outcomes</h2>
        <table class="data-table">
            <thead><tr><th>Referral Status</th><th>Count</th></tr></thead>
            <tbody>
            <?php foreach ($referralOutcomes as $r): ?>
                <tr><td><?= h($r['referral_status']) ?></td><td><?= (int) $r['n'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
