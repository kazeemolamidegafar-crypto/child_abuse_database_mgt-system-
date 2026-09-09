<?php
require_once __DIR__ . '/../config.php';
require_login();

$caseId = (int) ($_GET['id'] ?? 0);
$case = get_case_or_403($caseId);

$notesStmt = db()->prepare(
    'SELECT n.*, u.full_name AS author_name FROM case_notes n
     JOIN users u ON u.user_id = n.author_user_id
     WHERE n.case_id = :id ORDER BY n.created_at DESC'
);
$notesStmt->execute(['id' => $caseId]);
$notes = $notesStmt->fetchAll();

$refStmt = db()->prepare('SELECT * FROM referrals WHERE case_id = :id ORDER BY created_at DESC');
$refStmt->execute(['id' => $caseId]);
$referrals = $refStmt->fetchAll();

$caseworkers = db()->query("SELECT user_id, full_name FROM users WHERE role = 'caseworker' AND is_active = 1")->fetchAll();

log_action($_SESSION['user_id'], $_SESSION['username'], 'VIEW_CASE', 'cases', $caseId);

$canEdit = can_edit_case($case);

$pageTitle = 'Case ' . $case['case_reference_number'];
include __DIR__ . '/../includes/header.php';
?>
<h1>Case <?= h($case['case_reference_number']) ?>
    <span class="badge status-<?= h(str_replace(' ', '', $case['status'])) ?>"><?= h($case['status']) ?></span>
</h1>

<div class="two-col">
    <div class="form-card">
        <h2>Child Record</h2>
        <p><b>Name:</b> <?= h($case['child_name']) ?></p>
        <p><b>Reference Code:</b> <?= h($case['child_reference_code']) ?></p>
        <?php if (in_array(current_role(), ['administrator', 'caseworker'], true)): ?>
            <p><b>Date of Birth:</b> <?= h($case['date_of_birth'] ?: '—') ?></p>
            <p><b>Guardian Contact:</b> <?= h($case['guardian_contact'] ?: '—') ?></p>
            <p><b>Address:</b> <?= h($case['address'] ?: '—') ?></p>
        <?php else: ?>
            <p class="muted">Additional identifying details are restricted to the assigned caseworker and administrator.</p>
        <?php endif; ?>
    </div>

    <div class="form-card">
        <h2>Case Details</h2>
        <p><b>Category of Concern:</b> <?= h($case['category_of_concern']) ?></p>
        <p><b>Date Reported:</b> <?= h($case['date_reported']) ?></p>
        <p><b>Summary:</b> <?= h($case['summary'] ?: '—') ?></p>
        <p><b>Last Updated:</b> <?= h($case['updated_at']) ?></p>

        <?php if (current_role() === 'administrator'): ?>
            <form method="post" action="/admin/assign_case.php">
                <?= csrf_field() ?>
                <input type="hidden" name="case_id" value="<?= (int) $case['case_id'] ?>">
                <label>Assign Caseworker</label>
                <select name="caseworker_id">
                    <option value="">&mdash; Unassigned &mdash;</option>
                    <?php foreach ($caseworkers as $cw): ?>
                        <option value="<?= (int) $cw['user_id'] ?>" <?= (int) $case['assigned_caseworker_id'] === (int) $cw['user_id'] ? 'selected' : '' ?>>
                            <?= h($cw['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-small">Save Assignment</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="form-card">
    <h2>Update Status</h2>
    <form method="post" action="/case/update_status.php">
        <?= csrf_field() ?>
        <input type="hidden" name="case_id" value="<?= (int) $case['case_id'] ?>">
        <select name="status">
            <?php foreach (CASE_STATUSES as $s): ?>
                <option value="<?= h($s) ?>" <?= $s === $case['status'] ? 'selected' : '' ?>><?= h($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-primary">Update Status</button>
    </form>
</div>
<?php endif; ?>

<div class="two-col">
    <div class="form-card">
        <h2>Case Notes</h2>
        <?php if ($canEdit): ?>
            <form method="post" action="/case/add_note.php">
                <?= csrf_field() ?>
                <input type="hidden" name="case_id" value="<?= (int) $case['case_id'] ?>">
                <textarea name="note_text" rows="3" placeholder="Add a dated case note..." required></textarea>
                <button type="submit" class="btn-small">Add Note</button>
            </form>
        <?php endif; ?>
        <ul class="note-list">
            <?php if ($notes): ?>
                <?php foreach ($notes as $n): ?>
                    <li><b><?= h($n['author_name']) ?></b> &mdash; <span class="muted"><?= h($n['created_at']) ?></span><br><?= nl2br(h($n['note_text'])) ?></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="muted">No notes recorded.</li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="form-card">
        <h2>Referrals</h2>
        <?php if ($canEdit): ?>
            <form method="post" action="/case/add_referral.php">
                <?= csrf_field() ?>
                <input type="hidden" name="case_id" value="<?= (int) $case['case_id'] ?>">
                <select name="referred_to" required>
                    <option value="">Refer to&hellip;</option>
                    <?php foreach (REFERRAL_TARGETS as $t): ?>
                        <option value="<?= h($t) ?>"><?= h($t) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date_referred" value="<?= h(date('Y-m-d')) ?>">
                <button type="submit" class="btn-small">Create Referral</button>
            </form>
        <?php endif; ?>
        <ul class="note-list">
            <?php if ($referrals): ?>
                <?php foreach ($referrals as $r): ?>
                    <li>
                        <b><?= h($r['referred_to']) ?></b> &mdash;
                        <span class="badge"><?= h($r['referral_status']) ?></span>
                        <span class="muted">(<?= h($r['date_referred']) ?>)</span>
                        <?php if ($r['outcome_notes']): ?><br><?= nl2br(h($r['outcome_notes'])) ?><?php endif; ?>
                        <?php if ($canEdit): ?>
                            <form method="post" action="/case/update_referral_status.php" class="inline-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="case_id" value="<?= (int) $case['case_id'] ?>">
                                <input type="hidden" name="referral_id" value="<?= (int) $r['referral_id'] ?>">
                                <select name="referral_status">
                                    <?php foreach (REFERRAL_STATUSES as $rs): ?>
                                        <option value="<?= h($rs) ?>" <?= $rs === $r['referral_status'] ? 'selected' : '' ?>><?= h($rs) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="outcome_notes" placeholder="Outcome notes" value="<?= h($r['outcome_notes'] ?? '') ?>">
                                <button type="submit" class="btn-small">Update</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="muted">No referrals recorded.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<p><a href="/index.php">&larr; Back to dashboard</a></p>
<?php include __DIR__ . '/../includes/footer.php'; ?>
