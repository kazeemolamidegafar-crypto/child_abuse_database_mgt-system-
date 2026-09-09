<?php
/**
 * includes/functions.php
 *
 * Authentication, role-based authorization, and audit-logging helpers.
 * Authorization is enforced here on every protected page load -- the
 * interface hides controls a role shouldn't see, but that is a
 * usability convenience only. The real access-control boundary is
 * require_login() / require_role(), consistent with the "server
 * enforces, client merely reflects" principle from the system design.
 */

const CASE_STATUSES = ['Reported', 'Under Review', 'Referred', 'Under Investigation', 'Closed'];
const REFERRAL_STATUSES = ['Pending', 'Acknowledged', 'Resolved'];
const REFERRAL_TARGETS = [
    'Law Enforcement',
    'Medical Services',
    'Court / Legal Services',
    'Psychosocial / Counselling Services',
    'Other Agency',
];
const CATEGORIES = [
    'Physical Abuse',
    'Emotional Abuse',
    'Neglect',
    'Sexual Abuse',
    'Exploitation',
    'Other Safeguarding Concern',
];

/** Escape output for safe HTML rendering. */
function h(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function current_role(): ?string {
    return $_SESSION['role'] ?? null;
}

/** Redirect helper. */
function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}

/** Require any authenticated user; otherwise redirect to login. */
function require_login(): void {
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

/** Require the current user to hold one of the given roles, else 403. */
function require_role(string ...$allowedRoles): void {
    require_login();
    if (!in_array(current_role(), $allowedRoles, true)) {
        forbidden();
    }
}

function forbidden(): never {
    if (is_logged_in()) {
        log_action($_SESSION['user_id'], $_SESSION['username'], 'ACCESS_DENIED', null, null, $_SERVER['REQUEST_URI'] ?? null);
    }
    http_response_code(403);
    include __DIR__ . '/../error.php';
    exit;
}

/**
 * Write an append-only audit-trail entry. There is deliberately no
 * corresponding "update" or "delete" audit function anywhere in the
 * codebase, and no UI action to edit a log row.
 */
function log_action(?int $userId, ?string $username, string $action, ?string $targetEntity = null, ?int $targetId = null, ?string $details = null): void {
    $stmt = db()->prepare(
        'INSERT INTO audit_logs (user_id, username, action, target_entity, target_id, details, ip_address)
         VALUES (:user_id, :username, :action, :target_entity, :target_id, :details, :ip_address)'
    );
    $stmt->execute([
        'user_id'       => $userId,
        'username'      => $username,
        'action'        => $action,
        'target_entity' => $targetEntity,
        'target_id'     => $targetId,
        'details'       => $details,
        'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

/** Generate a short, unique-looking reference code, e.g. CASE-9F8E7D2A. */
function generate_reference(string $prefix): string {
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Fetch a case (joined with limited child fields) enforcing the
 * role-based visibility rule:
 *   administrator  -> any case
 *   caseworker     -> only if assigned to them
 *   intake_officer -> only if they registered it (view only)
 */
function get_case_or_403(int $caseId): array {
    $stmt = db()->prepare(
        'SELECT c.*, ch.full_name AS child_name, ch.child_reference_code,
                ch.date_of_birth, ch.guardian_contact, ch.address
         FROM cases c
         JOIN children ch ON ch.child_id = c.child_id
         WHERE c.case_id = :id'
    );
    $stmt->execute(['id' => $caseId]);
    $case = $stmt->fetch();

    if (!$case) {
        http_response_code(404);
        include __DIR__ . '/../error.php';
        exit;
    }

    $role = current_role();
    $userId = $_SESSION['user_id'];

    if ($role === 'administrator') {
        return $case;
    }
    if ($role === 'caseworker' && (int) $case['assigned_caseworker_id'] === $userId) {
        return $case;
    }
    if ($role === 'intake_officer' && (int) $case['reported_by_user_id'] === $userId) {
        return $case;
    }

    forbidden();
}

/** Whether the current user may edit (status/notes/referrals on) a given case. */
function can_edit_case(array $case): bool {
    $role = current_role();
    if ($role === 'administrator') {
        return true;
    }
    if ($role === 'caseworker') {
        return (int) $case['assigned_caseworker_id'] === (int) $_SESSION['user_id'];
    }
    return false;
}

/** Simple CSRF token helpers. */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Invalid or expired form submission (CSRF check failed). Please go back and try again.');
    }
}
