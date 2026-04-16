<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$currentUsername = (string) currentUser();
$accounts = loadUserAccounts();
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid request token. Refresh and try again.';
        $messageType = 'danger';
    } else {
        $action = (string) ($_POST['action'] ?? 'update');

        if ($action === 'create') {
            $username = (string) ($_POST['username'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $role = (string) ($_POST['role'] ?? 'user');
            $status = (string) ($_POST['status'] ?? 'active');

            if (createUserAccountByAdmin($username, $password, $role, $status)) {
                $message = 'User created successfully.';
                $accounts = loadUserAccounts();
            } else {
                $message = 'Unable to create user. Ensure username is unique and password has at least 6 characters.';
                $messageType = 'danger';
            }
        } elseif ($action === 'delete') {
            $username = (string) ($_POST['username'] ?? '');
            if (sanitizedUsername($username) === sanitizedUsername($currentUsername)) {
                $message = 'You cannot delete your own account.';
                $messageType = 'danger';
            } elseif (deleteUserAccount($username)) {
                $message = 'User deleted successfully.';
                $accounts = loadUserAccounts();
            } else {
                $message = 'Unable to delete user. Make sure at least one active admin remains.';
                $messageType = 'danger';
            }
        } else {
            $originalUsername = (string) ($_POST['original_username'] ?? '');
            $newUsername = (string) ($_POST['username'] ?? '');
            $role = (string) ($_POST['role'] ?? 'user');
            $status = (string) ($_POST['status'] ?? 'active');

            if (sanitizedUsername($originalUsername) === sanitizedUsername($currentUsername)
                && ($role !== 'admin' || $status === 'suspended')
            ) {
                $message = 'You cannot remove admin access or suspend your own account.';
                $messageType = 'danger';
            } elseif (updateUserAccount($originalUsername, $newUsername, $role, $status)) {
                if (sanitizedUsername($originalUsername) === sanitizedUsername($currentUsername)) {
                    $_SESSION['username'] = sanitizedUsername($newUsername);
                    $currentUsername = sanitizedUsername($newUsername);
                }
                $message = 'User updated successfully.';
                $accounts = loadUserAccounts();
            } else {
                $message = 'Unable to update user. Check for duplicate username or invalid values.';
                $messageType = 'danger';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users - Resume Creator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand"><i class="fa-solid fa-users-gear"></i> Admin</span>
        <div class="ms-auto">
            <a href="dashboard.php" class="btn btn-sm btn-outline-light me-2">Dashboard</a>
            <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <h1 class="h4 mb-3">Manage Users</h1>

    <?php if ($message !== ''): ?>
        <div class="alert alert-<?= h($messageType) ?>"><?= h($message) ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">Create User</h2>
            <form method="post" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="action" value="create">
                <div class="col-md-3">
                    <label class="form-label" for="create-username">Username</label>
                    <input id="create-username" type="text" class="form-control" name="username" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="create-password">Password</label>
                    <input id="create-password" type="password" class="form-control" name="password" minlength="6" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="create-role">Role</label>
                    <select id="create-role" class="form-select" name="role">
                        <option value="user" selected>User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="create-status">Status</label>
                    <select id="create-status" class="form-select" name="status">
                        <option value="active" selected>Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle bg-white">
            <thead>
            <tr>
                <th>Username</th>
                <th>Role</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php $formIndex = 0; ?>
            <?php foreach ($accounts as $username => $account): ?>
                <?php $formId = 'user-form-' . $formIndex; ?>
                <?php $deleteFormId = 'delete-form-' . $formIndex; ?>
                <tr>
                    <td>
                        <form method="post" id="<?= h($formId) ?>">
                            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="original_username" value="<?= h((string) $username) ?>">
                        </form>
                        <form method="post" id="<?= h($deleteFormId) ?>">
                            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="username" value="<?= h((string) $username) ?>">
                        </form>
                        <input type="text" class="form-control" name="username" form="<?= h($formId) ?>" value="<?= h((string) $username) ?>" required>
                    </td>
                    <td>
                        <select class="form-select" name="role" form="<?= h($formId) ?>">
                            <option value="user" <?= (($account['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= (($account['role'] ?? 'user') === 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-select" name="status" form="<?= h($formId) ?>">
                            <option value="active" <?= (($account['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="suspended" <?= (($account['status'] ?? 'active') === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </td>
                    <td class="text-end">
                        <button type="submit" class="btn btn-sm btn-primary me-2" form="<?= h($formId) ?>">Save</button>
                        <button type="submit" class="btn btn-sm btn-outline-danger" form="<?= h($deleteFormId) ?>" aria-label="Delete user <?= h((string) $username) ?>" onclick="return confirm(<?= htmlspecialchars((string) json_encode('Delete user ' . (string) $username . '?', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>);">Delete</button>
                    </td>
                </tr>
                <?php $formIndex++; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
