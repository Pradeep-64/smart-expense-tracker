<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

function isAdminLoggedIn(): bool
{
    return isset($_SESSION['admin_id']);
}

function requireAdminLogin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    checkAdminSessionTimeout();
}

function adminBasePath(): string
{
    return '/admin';
}
