<?php
require_once __DIR__ . '/../config.php';
require_role('administrator');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_user') {
    verify_csrf();

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (!in_array($role, ['administrator', 'caseworker', 'intake_officer'], true)) {
        flash('error', 'Invalid role selected.');
    } elseif ($fullName === '' || $username === '' || $password === '') {
        flash('error', 'All fields are required.');
    } elseif (strlen($password) < 8) {
        flash('error', 'Password must be at least 8 characters.');
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO users (full_name, username, password_hash, role) VALUES (:full_name, :username, :password_hash, :role)'
            );
            $stmt->execute([
                'full_name'      => $fullName,
                'username'       => $username,
                'password_hash'  => password_hash($password, PASSWORD_DEFAULT),
                'role'           => $role,
            ]);
            $newId = (int) db()->lastInsertId();
            log_action($_SESSION['user_id'], $_SESSION['username'], 'CREATE_USER', 'users', $newId, "Created user '$username' with role $role");
            flash('success', "User '$username' created.");
        } catch (PDOException $e) {
            flash('error', 'Could not create user (username may already be taken).');
        }
    }
    redirect('/admin/users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_user') {
    verify_csrf();
    $userId = (int) ($_POST['user_id'] ?? 0);

    $stmt = db()->prepare('SELECT * FROM users WHERE user_id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    if ($user) {
        $newStatus = $user['is_active'] ? 0 : 1;
        $upd = db()->prepare('UPDATE users SET is_active = :status WHERE user_id = :id');
        $upd->execute(['status' => $newStatus, 'id' => $userId]);

        $action = $newStatus ? 'REACTIVATE_USER' : 'DEACTIVATE_USER';
        log_action($_SESSION['user_id'], $_SESSION['username'], $action, 'users', $userId);
        flash('success', 'User account updated.');
    }
    redirect('/admin/users.php');
}

$users = db()->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>
<h1>User &amp; Role Management</h1>

<div class="two-col">
    <div>
        <h2>Create Account</h2>
        <form method="post" class="form-card">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_user">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
            <label>Username</label>
            <input type="text" name="username" required>
            <label>Temporary Password (min. 8 characters)</label>
            <input type="password" name="password" required minlength="8">
            <label>Role</label>
            <select name="role" required>
                <option value="administrator">Administrator</option>
                <option value="caseworker">Caseworker</option>
                <option value="intake_officer">Intake / Reporting Officer</option>
            </select>
            <button type="submit" class="btn-primary">Create User</button>
        </form>
    </div>

    <div>
        <h2>Existing Accounts</h2>
        <table class="data-table">
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= h($u['full_name']) ?></td>
                    <td><?= h($u['username']) ?></td>
                    <td><?= h(str_replace('_', ' ', $u['role'])) ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge badge-active">Active</span>
                        <?php else: ?>
                            <span class="badge badge-inactive">Deactivated</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle_user">
                            <input type="hidden" name="user_id" value="<?= (int) $u['user_id'] ?>">
                            <button type="submit" class="btn-small">
                                <?= $u['is_active'] ? 'Deactivate' : 'Reactivate' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
