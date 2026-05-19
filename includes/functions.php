<?php
declare(strict_types=1);

const SESSION_TIMEOUT_SECONDS = 3600;

function formatMoney(float $amount): string
{
    return 'Rs ' . number_format($amount, 2);
}

function checkSessionTimeout(): void
{
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }
    if (time() - (int) $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

function checkAdminSessionTimeout(): void
{
    if (!isset($_SESSION['admin_last_activity'])) {
        $_SESSION['admin_last_activity'] = time();
        return;
    }
    if (time() - (int) $_SESSION['admin_last_activity'] > SESSION_TIMEOUT_SECONDS) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['admin_last_activity'] = time();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
