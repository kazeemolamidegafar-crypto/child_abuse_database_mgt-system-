<?php
require_once __DIR__ . '/../config.php';
require_role('caseworker', 'administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}
verify_csrf();

$caseId = (int) ($_POST['case_id'] ?? 0);
$referralId = (int) ($_POST['referral_id'] ?? 0);
$case = get_case_or_403($caseId);

if (!can_edit_case($case)) {
    forbidden();
}

$newStatus = $_POST['referral_status'] ?? '';
$outcomeNotes = trim($_POST['outcome_notes'] ?? '');

if (!in_array($newStatus, REFERRAL_STATUSES, true)) {
    flash('error', 'Invalid referral status.');
    redirect('/case/view.php?id=' . $caseId);
}

$stmt = db()->prepare(
    'UPDATE referrals SET referral_status = :status, outcome_notes = :notes
     WHERE referral_id = :id AND case_id = :case_id'
);
$stmt->execute(['status' => $newStatus, 'notes' => $outcomeNotes, 'id' => $referralId, 'case_id' => $caseId]);

log_action($_SESSION['user_id'], $_SESSION['username'], 'UPDATE_REFERRAL_STATUS', 'referrals', $referralId, "status=$newStatus");
flash('success', 'Referral updated.');
redirect('/case/view.php?id=' . $caseId);
