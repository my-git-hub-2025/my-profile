<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function ensureDataStore(): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0700, true);
    }

    if (!file_exists(USERS_FILE)) {
        file_put_contents(USERS_FILE, "");
        chmod(USERS_FILE, 0600);
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function sanitizedUsername(string $username): string
{
    return strtolower((string) preg_replace('/[^a-zA-Z0-9_]/', '', $username));
}

function parseUserRecord(string $line): array
{
    [$username, $passwordHash, $role] = array_pad(explode('|', trim($line), 3), 3, '');
    if ($username === '' || $passwordHash === '') {
        return ['', '', ''];
    }

    $role = $role === 'admin' ? 'admin' : 'user';

    return [$username, $passwordHash, $role];
}

function loadUserAccounts(): array
{
    ensureDataStore();

    $accounts = [];
    $lines = file(USERS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($lines as $line) {
        [$username, $passwordHash, $role] = parseUserRecord($line);
        if ($username !== '' && $passwordHash !== '') {
            $accounts[$username] = [
                'password_hash' => $passwordHash,
                'role' => $role,
            ];
        }
    }

    return $accounts;
}

function registerUser(string $username, string $password): bool
{
    ensureDataStore();
    $username = sanitizedUsername($username);
    if ($username === '' || strlen($password) < 6) {
        return false;
    }

    $handle = fopen(USERS_FILE, 'c+');
    if ($handle === false) {
        return false;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return false;
    }

    rewind($handle);
    $users = [];
    while (($line = fgets($handle)) !== false) {
        [$existingUsername, $passwordHash, $role] = parseUserRecord($line);
        if ($existingUsername !== '' && $passwordHash !== '') {
            $users[$existingUsername] = [
                'password_hash' => $passwordHash,
                'role' => $role,
            ];
        }
    }

    if (isset($users[$username])) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = count($users) === 0 ? 'admin' : 'user';
    fseek($handle, 0, SEEK_END);
    fwrite($handle, $username . '|' . $hash . '|' . $role . PHP_EOL);
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    $defaultData = [
        'template' => '1',
        'full_name' => '',
        'title' => '',
        'email' => '',
        'phone' => '',
        'summary' => '',
        'education' => '',
        'experience' => '',
        'skills' => '',
    ];

    saveResumeData($username, $defaultData);
    return true;
}

function authenticateUser(string $username, string $password): bool
{
    $username = sanitizedUsername($username);
    $users = loadUserAccounts();

    return isset($users[$username]) && password_verify($password, $users[$username]['password_hash']);
}

function userDataPath(string $username): string
{
    return DATA_DIR . '/' . sanitizedUsername($username) . '.txt';
}

function loadResumeData(string $username): array
{
    $defaults = [
        'template' => '1',
        'full_name' => '',
        'title' => '',
        'email' => '',
        'phone' => '',
        'summary' => '',
        'education' => '',
        'experience' => '',
        'skills' => '',
    ];

    $file = userDataPath($username);
    if (!file_exists($file)) {
        return $defaults;
    }

    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) {
        return $defaults;
    }

    return array_merge($defaults, $data);
}

function saveResumeData(string $username, array $data): bool
{
    ensureDataStore();
    $file = userDataPath($username);

    $defaults = [
        'template' => '1',
        'full_name' => '',
        'title' => '',
        'email' => '',
        'phone' => '',
        'summary' => '',
        'education' => '',
        'experience' => '',
        'skills' => '',
    ];

    $payload = array_merge($defaults, $data);
    $saved = false !== file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT), LOCK_EX);
    if ($saved) {
        chmod($file, 0600);
    }

    return $saved;
}

function currentUser(): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $username = $_SESSION['username'] ?? null;
    return is_string($username) ? $username : null;
}

function requireLogin(): void
{
    if (currentUser() === null) {
        header('Location: login.php');
        exit;
    }
}

function templateName(string $id): string
{
    return 'Template ' . $id;
}

function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function isValidCsrfToken(?string $token): bool
{
    if ($token === null) {
        return false;
    }

    return hash_equals(csrfToken(), $token);
}
