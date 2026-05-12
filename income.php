<?php
declare(strict_types=1);
require_once "config/db.php";
require_once "includes/auth.php";
requireLogin();
$userId = (int) $_SESSION["user_id"];

if (isset($_POST["add_income"])) {
    $source = trim($_POST["source"]);
    $amount = (float) $_POST["amount"];
    $date = $_POST["date"];
    $description = trim($_POST["description"]);
    $stmt = $conn->prepare("INSERT INTO income(user_id, source, amount, date, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $userId, $source, $amount, $date, $description);
    $stmt->execute();
}

if (isset($_POST["update_income"])) {
    $id = (int) $_POST["id"];
    $source = trim($_POST["source"]);
    $amount = (float) $_POST["amount"];
    $date = $_POST["date"];
    $description = trim($_POST["description"]);
    $stmt = $conn->prepare("UPDATE income SET source=?, amount=?, date=?, description=? WHERE id=? AND user_id=?");
    $stmt->bind_param("sdssii", $source, $amount, $date, $description, $id, $userId);
    $stmt->execute();
}

if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    $conn->query("DELETE FROM income WHERE id=$id AND user_id=$userId");
    header("Location: income.php");
    exit;
}

$editData = null;
if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];
    $editData = $conn->query("SELECT * FROM income WHERE id=$id AND user_id=$userId")->fetch_assoc();
}

$rows = $conn->query("SELECT * FROM income WHERE user_id=$userId ORDER BY date DESC");

include "includes/header.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Income Management</h3>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="id" value="<?= $editData["id"] ?? "" ?>">
            <div class="col-md-3"><input class="form-control" name="source" placeholder="Income Source" value="<?= htmlspecialchars($editData["source"] ?? "") ?>" required></div>
            <div class="col-md-2"><input type="number" step="0.01" class="form-control" name="amount" placeholder="Amount" value="<?= $editData["amount"] ?? "" ?>" required></div>
            <div class="col-md-2"><input type="date" class="form-control" name="date" value="<?= $editData["date"] ?? date("Y-m-d") ?>" required></div>
            <div class="col-md-3"><input class="form-control" name="description" placeholder="Description" value="<?= htmlspecialchars($editData["description"] ?? "") ?>"></div>
            <div class="col-md-2 d-grid">
                <?php if ($editData): ?>
                    <button class="btn btn-warning" name="update_income">Update</button>
                <?php else: ?>
                    <button class="btn btn-primary" name="add_income">Add Income</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5>Income History</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Source</th><th>Amount</th><th>Date</th><th>Description</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($row = $rows->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["source"]) ?></td>
                        <td>Rs <?= number_format((float) $row["amount"], 2) ?></td>
                        <td><?= htmlspecialchars($row["date"]) ?></td>
                        <td><?= htmlspecialchars((string) $row["description"]) ?></td>
                        <td>
                            <a href="income.php?edit=<?= $row["id"] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                            <a href="income.php?delete=<?= $row["id"] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this income?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include "includes/footer.php"; ?>
