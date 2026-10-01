<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_role(array|string $roles): void
{
    require_login();
    $roles = (array)$roles;
    if (!in_array(current_user()['rol'], $roles, true)) {
        http_response_code(403);
        exit('Acceso denegado');
    }
}

function redirect_dashboard(): string
{
    $u = current_user();
    if (!$u) {
        return BASE_URL . '/login.php';
    }

    return match ($u['rol']) {
        'administrador' => BASE_URL . '/admin/dashboard.php',
        'docente'       => BASE_URL . '/docente/dashboard.php',
        'estudiante'    => BASE_URL . '/estudiante/dashboard.php',
        default         => BASE_URL . '/login.php',
    };
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
            http_response_code(419);
            exit('CSRF token inválido');
        }
    }
}

function flash(string $key, ?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return null;
    }

    $m = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $m;
}