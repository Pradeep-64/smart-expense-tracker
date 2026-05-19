<?php
declare(strict_types=1);
require_once __DIR__ . "/includes/auth.php";

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Expense Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh">
<div class="container text-center">
    <h1 class="mb-4">Smart Expense Tracker</h1>
    <p class="lead text-muted mb-4">Budget analysis, spending insights &amp; admin analytics</p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="login.php" class="btn btn-success btn-lg">User Login</a>
        <a href="register.php" class="btn btn-outline-primary btn-lg">Register</a>
        <a href="admin/login.php" class="btn btn-dark btn-lg">Admin Panel</a>
    </div>
</div>
</body>
</html>
