<?php
declare(strict_types=1);
require_once "config/db.php";
require_once "includes/auth.php";
requireLogin();
$userId = (int) $_SESSION["user_id"];
$currentMonth = date("Y-m");

if (isset($_POST["set_budget"])) {
    $categoryId = (int) $_POST["category_id"];
    $amount = (float) $_POST["amount"];
    $monthYear = $_POST["month_year"];
    $check = $conn->prepare("SELECT id FROM budgets WHERE user_id=? AND category_id=? AND month_year=?");
    $check->bind_param("iis", $userId, $categoryId, $monthYear);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    if ($exists) {
        $stmt = $conn->prepare("UPDATE budgets SET budget_amount=? WHERE id=?");
        $stmt->bind_param("di", $amount, $exists["id"]);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO budgets(user_id, category_id, month_year, budget_amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisd", $userId, $categoryId, $monthYear, $amount);
        $stmt->execute();
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$budgetRows = $conn->query("
    SELECT b.*, c.name AS category_name,
           COALESCE((SELECT SUM(e.amount) FROM expenses e WHERE e.user_id=b.user_id AND e.category_id=b.category_id AND DATE_FORMAT(e.date,'%Y-%m')=b.month_year),0) AS spent
    FROM budgets b
    JOIN categories c ON c.id=b.category_id
    WHERE b.user_id=$userId
    ORDER BY b.month_year DESC, c.name
");

include "includes/header.php";
?>
<h3 class="mb-3">Budget Management</h3>
<div class="card mb-4">
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <select name="category_id" class="form-select" required>
                    <option value="">Select Category</option>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                    <option value="<?= $c["id"] ?>"><?= htmlspecialchars($c["name"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3"><input type="month" name="month_year" class="form-control" value="<?= $currentMonth ?>" required></div>
            <div class="col-md-3"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Budget Amount" required></div>
            <div class="col-md-2 d-grid"><button name="set_budget" class="btn btn-primary">Save Budget</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5>Budget Tracking</h5>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Month</th><th>Category</th><th>Budget</th><th>Spent</th><th>Remaining</th><th>Status</th></tr></thead>
                <tbody>
                <?php while ($row = $budgetRows->fetch_assoc()):
                    $remaining = (float) $row["budget_amount"] - (float) $row["spent"];
                    $isExceeded = $remaining < 0;
                ?>
                    <tr class="<?= $isExceeded ? "table-danger" : "" ?>">
                        <td><?= htmlspecialchars($row["month_year"]) ?></td>
                        <td><?= htmlspecialchars($row["category_name"]) ?></td>
                        <td>Rs <?= number_format((float) $row["budget_amount"], 2) ?></td>
                        <td>Rs <?= number_format((float) $row["spent"], 2) ?></td>
                        <td>Rs <?= number_format($remaining, 2) ?></td>
                        <td><?= $isExceeded ? '<span class="badge bg-danger">Exceeded</span>' : '<span class="badge bg-success">Under Control</span>' ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <small class="text-muted">Rows in red indicate budget exceeded warning.</small>
    </div>
</div>
<?php include "includes/footer.php"; ?>
