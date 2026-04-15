<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
requireLogin();

if (!isValidCsrfToken($_GET['token'] ?? null)) {
    http_response_code(403);
    echo 'Invalid request token.';
    exit;
}

header('Location: preview.php?print=1');
exit;
