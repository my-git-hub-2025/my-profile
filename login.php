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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request token. Please refresh and try again.';
    }

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($error === '' && authenticateUser((string) $username, (string) $password)) {
        $_SESSION['username'] = sanitizedUsername((string) $username);
        header('Location: dashboard.php');
        exit;
    }

    if ($error === '') {
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Resume Creator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3 text-center"><i class="fa-solid fa-user-lock"></i> Login</h1>
                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <input id="username" type="text" name="username" class="form-control" autocomplete="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input id="password" type="password" name="password" class="form-control" autocomplete="current-password" required>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Login</button>
                    </form>
                    <p class="text-center mt-3 mb-0">No account? <a href="register.php">Register</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
