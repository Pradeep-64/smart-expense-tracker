<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/admin_auth.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_last_activity']);
header('Location: login.php');
exit;
