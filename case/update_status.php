<?php
require_once __DIR__ . '/../config.php';
require_role('caseworker', 'administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}
verify_csrf();

$caseId = (int) ($_POST['case_id'] ?? 0);
$case = get_case_or_403($caseId);

if (!can_edit_case($case)) {
    forbidden();
}

$newStatus = $_POST['status'] ?? '';
if (!in_array($newStatus, CASE_STATUSES, true)) {
    flash('error', 'Invalid status.');
    redirect('/case/view.php?id=' . $caseId);
}

$oldStatus = $case['status'];
$stmt = db()->prepare('UPDATE cases SET status = :status WHERE case_id = :id');
$stmt->execute(['status' => $newStatus, 'id' => $caseId]);

log_action($_SESSION['user_id'], $_SESSION['username'], 'UPDATE_CASE_STATUS', 'cases', $caseId, "$oldStatus -> $newStatus");
flash('success', 'Case status updated.');
redirect('/case/view.php?id=' . $caseId);
