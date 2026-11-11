<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function is_authenticated(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['username']);
}

function require_auth(): void
{
    if (!is_authenticated()) {
        header('Location: login.php');
        exit();
    }
}
