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
            <?php foreach ($accounts as $username => $account): ?>
                <tr>
                    <form method="post">
                        <td>
                            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                            <input type="hidden" name="original_username" value="<?= h((string) $username) ?>">
                            <input type="text" class="form-control" name="username" value="<?= h((string) $username) ?>" required>
                        </td>
                        <td>
                            <select class="form-select" name="role">
                                <option value="user" <?= (($account['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= (($account['role'] ?? 'user') === 'admin') ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-select" name="status">
                                <option value="active" <?= (($account['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="suspended" <?= (($account['status'] ?? 'active') === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </td>
                        <td class="text-end">
                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
