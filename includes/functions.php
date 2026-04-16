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
    $line = trim($line);
    if (substr_count($line, '|') > 3) {
        return ['', '', 'user', 'active'];
    }

    [$username, $passwordHash, $role, $status] = array_pad(explode('|', $line, 4), 4, '');
    if ($username === '' || $passwordHash === '') {
        return ['', '', 'user', 'active'];
    }

    $role = $role === 'admin' ? 'admin' : 'user';
    $status = $status === 'suspended' ? 'suspended' : 'active';

    return [$username, $passwordHash, $role, $status];
}

function loadUserAccounts(): array
{
    ensureDataStore();

    $accounts = [];
    $lines = file(USERS_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($lines as $line) {
        [$username, $passwordHash, $role, $status] = parseUserRecord($line);
        if ($username !== '' && $passwordHash !== '') {
            $accounts[$username] = [
                'password_hash' => $passwordHash,
                'role' => $role,
                'status' => $status,
            ];
        }
    }

    return $accounts;
}

function saveUserAccounts(array $users): bool
{
    ensureDataStore();
    $handle = fopen(USERS_FILE, 'c+');
    if ($handle === false) {
        return false;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return false;
    }

    ftruncate($handle, 0);
    rewind($handle);
    foreach ($users as $username => $account) {
        $role = (($account['role'] ?? 'user') === 'admin') ? 'admin' : 'user';
        $status = (($account['status'] ?? 'active') === 'suspended') ? 'suspended' : 'active';
        $passwordHash = (string) ($account['password_hash'] ?? '');
        if ($username === '' || $passwordHash === '') {
            continue;
        }

        fwrite($handle, $username . '|' . $passwordHash . '|' . $role . '|' . $status . PHP_EOL);
    }

    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return true;
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
        [$existingUsername, $passwordHash, $role, $status] = parseUserRecord($line);
        if ($existingUsername !== '' && $passwordHash !== '') {
            $users[$existingUsername] = [
                'password_hash' => $passwordHash,
                'role' => $role,
                'status' => $status,
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
    $status = 'active';
    fseek($handle, 0, SEEK_END);
    fwrite($handle, $username . '|' . $hash . '|' . $role . '|' . $status . PHP_EOL);
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

    if (!saveResumeData($username, $defaultData)) {
        unset($users[$username]);
        saveUserAccounts($users);
        return false;
    }

    return true;
}

function createUserAccountByAdmin(string $username, string $password, string $role, string $status): bool
{
    $username = sanitizedUsername($username);
    $role = $role === 'admin' ? 'admin' : 'user';
    $status = $status === 'suspended' ? 'suspended' : 'active';
    if ($username === '' || strlen($password) < 6) {
        return false;
    }

    $users = loadUserAccounts();
    if (isset($users[$username])) {
        return false;
    }

    $users[$username] = [
        'password_hash' => (string) password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'status' => $status,
    ];

    if (!saveUserAccounts($users)) {
        return false;
    }

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

    return isset($users[$username])
        && !isUserSuspended($username, $users)
        && password_verify($password, $users[$username]['password_hash']);
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
    $username = currentUser();
    if ($username === null) {
        header('Location: login.php');
        exit;
    }

    $users = loadUserAccounts();
    if (!isset($users[$username]) || isUserSuspended($username, $users)) {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        header('Location: login.php?error=account_inactive');
        exit;
    }
}

function isUserSuspended(string $username, ?array $users = null): bool
{
    $username = sanitizedUsername($username);
    if ($users === null) {
        $users = loadUserAccounts();
    }

    return isset($users[$username]) && (($users[$username]['status'] ?? 'active') === 'suspended');
}

function currentUserRole(): string
{
    $username = currentUser();
    if ($username === null) {
        return 'user';
    }

    $users = loadUserAccounts();
    if (!isset($users[$username])) {
        return 'user';
    }

    return (($users[$username]['role'] ?? 'user') === 'admin') ? 'admin' : 'user';
}

function isAdmin(?string $username = null): bool
{
    if ($username === null) {
        return currentUserRole() === 'admin';
    }

    $username = sanitizedUsername($username);
    $users = loadUserAccounts();
    return isset($users[$username]) && (($users[$username]['role'] ?? 'user') === 'admin');
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

function updateUserAccount(string $originalUsername, string $newUsername, string $role, string $status): bool
{
    $originalUsername = sanitizedUsername($originalUsername);
    $newUsername = sanitizedUsername($newUsername);
    $role = $role === 'admin' ? 'admin' : 'user';
    $status = $status === 'suspended' ? 'suspended' : 'active';

    if ($originalUsername === '' || $newUsername === '') {
        return false;
    }

    $users = loadUserAccounts();
    if (!isset($users[$originalUsername])) {
        return false;
    }

    if ($originalUsername !== $newUsername && isset($users[$newUsername])) {
        return false;
    }

    if (($users[$originalUsername]['role'] ?? 'user') === 'admin' && ($role !== 'admin' || $status === 'suspended')) {
        $otherActiveAdminExists = false;
        foreach ($users as $username => $user) {
            if ($username === $originalUsername) {
                continue;
            }

            if (($user['role'] ?? 'user') === 'admin' && (($user['status'] ?? 'active') !== 'suspended')) {
                $otherActiveAdminExists = true;
                break;
            }
        }

        if (!$otherActiveAdminExists) {
            return false;
        }
    }

    $account = $users[$originalUsername];
    $account['role'] = $role;
    $account['status'] = $status;

    if ($originalUsername !== $newUsername) {
        unset($users[$originalUsername]);
        $users[$newUsername] = $account;
    } else {
        $users[$originalUsername] = $account;
    }

    if (!saveUserAccounts($users)) {
        return false;
    }

    if ($originalUsername !== $newUsername) {
        $oldDataFile = userDataPath($originalUsername);
        $newDataFile = userDataPath($newUsername);
        if (file_exists($oldDataFile) && !file_exists($newDataFile)) {
            rename($oldDataFile, $newDataFile);
        }
    }

    return true;
}

function deleteUserAccount(string $username): bool
{
    $username = sanitizedUsername($username);
    if ($username === '') {
        return false;
    }

    if (sanitizedUsername((string) currentUser()) === $username) {
        return false;
    }

    $users = loadUserAccounts();
    if (!isset($users[$username])) {
        return false;
    }

    if (($users[$username]['role'] ?? 'user') === 'admin'
        && (($users[$username]['status'] ?? 'active') !== 'suspended')
    ) {
        $otherActiveAdminExists = false;
        foreach ($users as $existingUsername => $user) {
            if ($existingUsername === $username) {
                continue;
            }

            if (($user['role'] ?? 'user') === 'admin' && (($user['status'] ?? 'active') !== 'suspended')) {
                $otherActiveAdminExists = true;
                break;
            }
        }

        if (!$otherActiveAdminExists) {
            return false;
        }
    }

    unset($users[$username]);
    if (!saveUserAccounts($users)) {
        return false;
    }

    $dataFile = userDataPath($username);
    if (file_exists($dataFile)) {
        unlink($dataFile);
    }

    return true;
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
