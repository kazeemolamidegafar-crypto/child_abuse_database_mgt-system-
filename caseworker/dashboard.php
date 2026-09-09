<?php
require_once __DIR__ . '/../config.php';
require_role('caseworker', 'administrator');

if (current_role() === 'administrator') {
    $cases = db()->query(
        'SELECT c.*, ch.full_name AS child_name FROM cases c
         JOIN children ch ON ch.child_id = c.child_id
         ORDER BY c.updated_at DESC'
    )->fetchAll();
} else {
    $stmt = db()->prepare(
        'SELECT c.*, ch.full_name AS child_name FROM cases c
         JOIN children ch ON ch.child_id = c.child_id
         WHERE c.assigned_caseworker_id = :uid
         ORDER BY c.updated_at DESC'
    );
    $stmt->execute(['uid' => $_SESSION['user_id']]);
    $cases = $stmt->fetchAll();
}

$pageTitle = current_role() === 'administrator' ? 'All Cases' : 'My Caseload';
include __DIR__ . '/../includes/header.php';
?>
<h1><?= current_role() === 'administrator' ? 'All Cases' : 'My Assigned Cases' ?></h1>

<table class="data-table">
    <thead><tr><th>Reference</th><th>Child</th><th>Category</th><th>Reported</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if ($cases): ?>
        <?php foreach ($cases as $c): ?>
            <tr>
                <td><?= h($c['case_reference_number']) ?></td>
                <td><?= h($c['child_name']) ?></td>
                <td><?= h($c['category_of_concern']) ?></td>
                <td><?= h($c['date_reported']) ?></td>
                <td><span class="badge status-<?= h(str_replace(' ', '', $c['status'])) ?>"><?= h($c['status']) ?></span></td>
                <td><a href="/case/view.php?id=<?= (int) $c['case_id'] ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="6">No cases assigned yet.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>
