<?php
declare(strict_types=1);
require_once "config/db.php";
require_once "includes/auth.php";

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = (int) $user["id"];
        $_SESSION["user_name"] = $user["name"];
        header("Location: dashboard.php");
        exit;
    }
    $message = "Invalid email or password.";
}
include "includes/header.php";
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow border-0">
            <div class="card-body p-4">
                <h3 class="mb-3">Login</h3>
                <?php if (isset($_GET["registered"])): ?><div class="alert alert-success">Registration successful. Login now.</div><?php endif; ?>
                <?php if ($message !== ""): ?><div class="alert alert-danger"><?= htmlspecialchars($message) ?></div><?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button class="btn btn-success w-100">Login</button>
                </form>
                <p class="mt-3 mb-0">New user? <a href="register.php">Create account</a></p>
            </div>
        </div>
    </div>
</div>
<?php include "includes/footer.php"; ?>
