<?php
declare(strict_types=1);

function attempt_login(string $email, string $password): bool
{
    $q = db()->prepare("SELECT * FROM users WHERE email = ? AND active = 1 LIMIT 1");
    $q->execute([strtolower(trim($email))]);
    $user = $q->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];

    return true;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_login(): void
{
    if (!current_user()) {
        redirect('/admin/login.php');
    }
}

function require_role(string $role): void
{
    require_login();
    $user = current_user();
    if (($user['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Você não tem permissão para acessar esta área.');
    }
}

function is_dev(): bool
{
    return (current_user()['role'] ?? null) === 'dev';
}