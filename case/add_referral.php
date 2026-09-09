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

$referredTo = $_POST['referred_to'] ?? '';
$dateReferred = $_POST['date_referred'] ?: date('Y-m-d');

if (!in_array($referredTo, REFERRAL_TARGETS, true)) {
    flash('error', 'Invalid referral target.');
    redirect('/case/view.php?id=' . $caseId);
}

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        "INSERT INTO referrals (case_id, referred_to, date_referred, referral_status, created_by_user_id)
         VALUES (:case_id, :referred_to, :date_referred, 'Pending', :created_by)"
    );
    $stmt->execute([
        'case_id'      => $caseId,
        'referred_to'  => $referredTo,
        'date_referred'=> $dateReferred,
        'created_by'   => $_SESSION['user_id'],
    ]);
    $referralId = (int) $pdo->lastInsertId();

    $pdo->prepare("UPDATE cases SET status = 'Referred' WHERE case_id = :id")->execute(['id' => $caseId]);

    $pdo->commit();

    log_action($_SESSION['user_id'], $_SESSION['username'], 'CREATE_REFERRAL', 'referrals', $referralId, "case_id=$caseId, referred_to=$referredTo");
    flash('success', 'Referral recorded and case status updated.');
} catch (Throwable $e) {
    $pdo->rollBack();
    flash('error', 'Could not record the referral.');
}

redirect('/case/view.php?id=' . $caseId);
