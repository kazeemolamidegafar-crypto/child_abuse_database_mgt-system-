<?php
require_once __DIR__ . '/../config.php';
require_role('intake_officer', 'administrator');

$q = trim($_GET['q'] ?? '');
$results = [];

if ($q !== '') {
    $stmt = db()->prepare(
        'SELECT child_id, child_reference_code, full_name, date_of_birth
         FROM children WHERE full_name LIKE :q OR child_reference_code LIKE :q
         LIMIT 20'
    );
    $stmt->execute(['q' => '%' . $q . '%']);
    $results = $stmt->fetchAll();

    log_action($_SESSION['user_id'], $_SESSION['username'], 'SEARCH_CHILD', 'children', null, "query='$q'");
}

if ($results): ?>
    <ul class="search-results-list">
        <?php foreach ($results as $r): ?>
            <li>
                <button type="button" class="pick-child"
                        data-id="<?= (int) $r['child_id'] ?>"
                        data-name="<?= h($r['full_name']) ?>"
                        data-ref="<?= h($r['child_reference_code']) ?>">
                    <?= h($r['full_name']) ?> &mdash; <code><?= h($r['child_reference_code']) ?></code>
                    <?php if ($r['date_of_birth']): ?>(DOB: <?= h($r['date_of_birth']) ?>)<?php endif; ?>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p class="muted">No matching child record found. A new record will be created below.</p>
<?php endif;
