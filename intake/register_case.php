<?php
require_once __DIR__ . '/../config.php';
require_role('intake_officer', 'administrator');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/intake/dashboard.php');
}
verify_csrf();

$pdo = db();
$existingChildId = trim($_POST['existing_child_id'] ?? '');
$category = $_POST['category_of_concern'] ?? '';
$dateReported = $_POST['date_reported'] ?: date('Y-m-d');
$summary = trim($_POST['summary'] ?? '');

$pdo->beginTransaction();
try {
    if ($existingChildId !== '') {
        $childId = (int) $existingChildId;
        log_action($_SESSION['user_id'], $_SESSION['username'], 'LINK_EXISTING_CHILD', 'children', $childId);
    } else {
        $fullName = trim($_POST['child_full_name'] ?? '');
        $dob = trim($_POST['child_dob'] ?? '') ?: null;
        $guardianContact = trim($_POST['guardian_contact'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($fullName === '') {
            $pdo->rollBack();
            flash('error', 'Child full name is required when no existing record is selected.');
            redirect('/intake/dashboard.php');
        }

        $refCode = generate_reference('CH');
        $stmt = $pdo->prepare(
            'INSERT INTO children (child_reference_code, full_name, date_of_birth, guardian_contact, address)
             VALUES (:ref, :name, :dob, :guardian, :address)'
        );
        $stmt->execute([
            'ref'      => $refCode,
            'name'     => $fullName,
            'dob'      => $dob,
            'guardian' => $guardianContact,
            'address'  => $address,
        ]);
        $childId = (int) $pdo->lastInsertId();
        log_action($_SESSION['user_id'], $_SESSION['username'], 'CREATE_CHILD_RECORD', 'children', $childId, "reference=$refCode");
    }

    if (!in_array($category, CATEGORIES, true)) {
        $pdo->rollBack();
        flash('error', 'A valid category of concern is required.');
        redirect('/intake/dashboard.php');
    }

    $caseRef = generate_reference('CASE');
    $stmt = $pdo->prepare(
        "INSERT INTO cases (case_reference_number, child_id, category_of_concern, date_reported, reported_by_user_id, status, summary)
         VALUES (:ref, :child_id, :category, :date_reported, :reporter, 'Reported', :summary)"
    );
    $stmt->execute([
        'ref'           => $caseRef,
        'child_id'      => $childId,
        'category'      => $category,
        'date_reported' => $dateReported,
        'reporter'      => $_SESSION['user_id'],
        'summary'       => $summary,
    ]);
    $caseId = (int) $pdo->lastInsertId();

    $pdo->commit();

    log_action($_SESSION['user_id'], $_SESSION['username'], 'CREATE_CASE', 'cases', $caseId, "reference=$caseRef, category=$category");
    flash('success', "Case $caseRef registered successfully.");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Could not register the case. Please check the form and try again.');
}

redirect('/intake/dashboard.php');
