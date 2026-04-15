<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function ensureDataStore(): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }

    if (!file_exists(USERS_FILE)) {
        file_put_contents(USERS_FILE, "");
    }
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function sanitizedUsername(string $username): string
{
    return strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $username) ?? '');
}

function loadUsers(): array
{
    ensureDataStore();

    $users = [];
    $lines = file(USERS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($lines as $line) {
        [$username, $passwordHash] = array_pad(explode('|', $line, 2), 2, '');
        if ($username !== '' && $passwordHash !== '') {
            $users[$username] = $passwordHash;
        }
    }

    return $users;
}

function registerUser(string $username, string $password): bool
{
    $username = sanitizedUsername($username);
    if ($username === '' || strlen($password) < 6) {
        return false;
    }

    $users = loadUsers();
    if (isset($users[$username])) {
        return false;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    file_put_contents(USERS_FILE, $username . '|' . $hash . PHP_EOL, FILE_APPEND | LOCK_EX);

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
    $users = loadUsers();

    return isset($users[$username]) && password_verify($password, $users[$username]);
}

function userDataPath(string $username): string
{
    return DATA_DIR . '/' . sanitizedUsername($username) . '.txt';
}

function loadResumeData(string $username): array
{
    $file = userDataPath($username);
    if (!file_exists($file)) {
        return [
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
    }

    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : [];
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
    return false !== file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT), LOCK_EX);
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
