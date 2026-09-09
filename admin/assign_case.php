<?php
require_once __DIR__ . '/../config.php';
require_role('administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}
verify_csrf();

$caseId = (int) ($_POST['case_id'] ?? 0);
$caseworkerId = $_POST['caseworker_id'] !== '' ? (int) $_POST['caseworker_id'] : null;

// Confirm the case exists (administrators may view/assign any case).
get_case_or_403($caseId);

$stmt = db()->prepare('UPDATE cases SET assigned_caseworker_id = :cw WHERE case_id = :id');
$stmt->execute(['cw' => $caseworkerId, 'id' => $caseId]);

log_action($_SESSION['user_id'], $_SESSION['username'], 'ASSIGN_CASE', 'cases', $caseId, 'Assigned to user_id ' . ($caseworkerId ?? 'null'));
flash('success', 'Case assigned.');
redirect('/case/view.php?id=' . $caseId);
