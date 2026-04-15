<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (currentUser() !== null) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request token. Please refresh and try again.';
    }

    $username = (string) ($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($error === '' && registerUser($username, $password)) {
        $success = 'Registration successful. You can login now.';
    } elseif ($error === '') {
        $error = 'Registration failed. Username may exist or password is too short (min 6).';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Resume Creator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3 text-center"><i class="fa-solid fa-user-plus"></i> Register</h1>
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success !== ''): ?>
                        <div class="alert alert-success"><?= h($success) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <input id="username" type="text" name="username" class="form-control" autocomplete="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input id="password" type="password" name="password" class="form-control" minlength="6" autocomplete="new-password" required>
                        </div>
                        <button class="btn btn-success w-100" type="submit">Create Account</button>
                    </form>
                    <p class="text-center mt-3 mb-0"><a href="login.php">Back to login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
