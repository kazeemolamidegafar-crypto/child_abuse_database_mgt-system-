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

$noteText = trim($_POST['note_text'] ?? '');
if ($noteText !== '') {
    $stmt = db()->prepare('INSERT INTO case_notes (case_id, author_user_id, note_text) VALUES (:case_id, :author, :text)');
    $stmt->execute(['case_id' => $caseId, 'author' => $_SESSION['user_id'], 'text' => $noteText]);
    $noteId = (int) db()->lastInsertId();

    log_action($_SESSION['user_id'], $_SESSION['username'], 'ADD_CASE_NOTE', 'case_notes', $noteId, "case_id=$caseId");
    flash('success', 'Note added.');
}

redirect('/case/view.php?id=' . $caseId);
