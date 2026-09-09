<?php
require_once __DIR__ . '/../config.php';
require_role('intake_officer', 'administrator');

$stmt = db()->prepare(
    'SELECT c.*, ch.full_name AS child_name FROM cases c
     JOIN children ch ON ch.child_id = c.child_id
     WHERE c.reported_by_user_id = :uid ORDER BY c.created_at DESC'
);
$stmt->execute(['uid' => $_SESSION['user_id']]);
$myCases = $stmt->fetchAll();

$pageTitle = 'Register a Case';
include __DIR__ . '/../includes/header.php';
?>
<h1>Register a New Report</h1>

<div class="form-card">
    <h2>Step 1 &mdash; Check for an existing child record</h2>
    <input type="text" id="child-search" placeholder="Search by name or reference code..." autocomplete="off">
    <div id="search-results"></div>
</div>

<form method="post" action="/intake/register_case.php" class="form-card" id="case-form">
    <?= csrf_field() ?>
    <h2>Step 2 &mdash; Child details</h2>
    <input type="hidden" name="existing_child_id" id="existing_child_id" value="">
    <div id="existing-child-banner" class="flash flash-info" style="display:none;"></div>

    <div id="new-child-fields">
        <label>Child Full Name</label>
        <input type="text" name="child_full_name">
        <label>Date of Birth</label>
        <input type="date" name="child_dob">
        <label>Guardian Contact</label>
        <input type="text" name="guardian_contact" placeholder="Phone / email">
        <label>Address</label>
        <input type="text" name="address">
    </div>

    <h2>Step 3 &mdash; Case details</h2>
    <label>Category of Concern</label>
    <select name="category_of_concern" required>
        <option value="">Select category&hellip;</option>
        <?php foreach (CATEGORIES as $c): ?>
            <option value="<?= h($c) ?>"><?= h($c) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Date Reported</label>
    <input type="date" name="date_reported" value="<?= h(date('Y-m-d')) ?>">

    <label>Summary of Report</label>
    <textarea name="summary" rows="4" placeholder="Brief, factual account of what was reported..."></textarea>

    <button type="submit" class="btn-primary">Register Case</button>
</form>

<h2>Cases You Have Registered</h2>
<table class="data-table">
    <thead><tr><th>Reference</th><th>Child</th><th>Category</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if ($myCases): ?>
        <?php foreach ($myCases as $c): ?>
            <tr>
                <td><?= h($c['case_reference_number']) ?></td>
                <td><?= h($c['child_name']) ?></td>
                <td><?= h($c['category_of_concern']) ?></td>
                <td><span class="badge status-<?= h(str_replace(' ', '', $c['status'])) ?>"><?= h($c['status']) ?></span></td>
                <td><a href="/case/view.php?id=<?= (int) $c['case_id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="5">You have not registered any cases yet.</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<script>
const searchBox = document.getElementById('child-search');
const resultsDiv = document.getElementById('search-results');
const existingIdField = document.getElementById('existing_child_id');
const newChildFields = document.getElementById('new-child-fields');
const banner = document.getElementById('existing-child-banner');
let debounce;

searchBox.addEventListener('input', () => {
    clearTimeout(debounce);
    const q = searchBox.value.trim();
    if (!q) { resultsDiv.innerHTML = ''; return; }
    debounce = setTimeout(() => {
        fetch('/intake/search_child.php?q=' + encodeURIComponent(q))
            .then(r => r.text())
            .then(html => resultsDiv.innerHTML = html);
    }, 300);
});

resultsDiv.addEventListener('click', (e) => {
    if (e.target.classList.contains('pick-child')) {
        existingIdField.value = e.target.dataset.id;
        banner.style.display = 'block';
        banner.textContent = 'Linking to existing record: ' + e.target.dataset.name + ' (' + e.target.dataset.ref + ')';
        newChildFields.style.display = 'none';
        resultsDiv.innerHTML = '';
        searchBox.value = '';
    }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
