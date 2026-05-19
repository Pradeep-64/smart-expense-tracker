<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';

function ensureAdminCredentials(mysqli $conn): void
{
    $res = $conn->query("SELECT admin_id, password FROM admin WHERE admin_email = 'admin@tracker.com' LIMIT 1");
    if (!$res || $res->num_rows === 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO admin (admin_name, admin_email, password) VALUES (?, ?, ?)');
        $name = 'System Admin';
        $email = 'admin@tracker.com';
        $stmt->bind_param('sss', $name, $email, $hash);
        $stmt->execute();
        return;
    }
    $row = $res->fetch_assoc();
    if (!password_verify('admin123', $row['password'])) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE admin SET password = ? WHERE admin_id = ?');
        $stmt->bind_param('si', $hash, $row['admin_id']);
        $stmt->execute();
    }
}

ensureAdminCredentials($conn);

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT admin_id, admin_name, password FROM admin WHERE admin_email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['admin_id'];
        $_SESSION['admin_name'] = $admin['admin_name'];
        $_SESSION['admin_last_activity'] = time();
        header('Location: dashboard.php');
        exit;
    }
    $message = 'Invalid admin credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body d-flex align-items-center justify-content-center">
<div class="container" style="max-width: 440px;">
    <div class="card admin-card shadow">
        <div class="card-body p-4">
            <h3 class="mb-3">Admin Login</h3>
            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert alert-warning">Session expired. Please login again.</div>
            <?php endif; ?>
            <?php if ($message !== ''): ?>
                <div class="alert alert-danger"><?= e($message) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="admin@tracker.com" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Login to Admin Panel</button>
            </form>
            <p class="mt-3 mb-0 small text-muted">Default: admin@tracker.com / admin123</p>
            <a href="../index.php" class="d-block mt-2 small">Back to user site</a>
        </div>
    </div>
</div>
</body>
</html>
