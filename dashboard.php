<?php
declare(strict_types=1);
require_once "config/db.php";
require_once "includes/auth.php";
requireLogin();

$userId = (int) $_SESSION["user_id"];

$incomeRow = $conn->query("SELECT COALESCE(SUM(amount),0) AS total_income FROM income WHERE user_id = $userId")->fetch_assoc();
$expenseRow = $conn->query("SELECT COALESCE(SUM(amount),0) AS total_expenses FROM expenses WHERE user_id = $userId")->fetch_assoc();

$totalIncome = (float) $incomeRow["total_income"];
$totalExpenses = (float) $expenseRow["total_expenses"];
$balance = $totalIncome - $totalExpenses;

$monthIncome = $conn->query("SELECT COALESCE(SUM(amount),0) AS m_income FROM income WHERE user_id = $userId AND DATE_FORMAT(date,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')")->fetch_assoc();
$monthExpense = $conn->query("SELECT COALESCE(SUM(amount),0) AS m_expense FROM expenses WHERE user_id = $userId AND DATE_FORMAT(date,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')")->fetch_assoc();
$monthlySavings = (float) $monthIncome["m_income"] - (float) $monthExpense["m_expense"];

$highestCategory = $conn->query("SELECT c.name, SUM(e.amount) AS total FROM expenses e JOIN categories c ON c.id=e.category_id WHERE e.user_id = $userId GROUP BY c.name ORDER BY total DESC LIMIT 1")->fetch_assoc();

$recentTx = $conn->query("
    (SELECT 'Income' AS type, source AS title, amount, date FROM income WHERE user_id = $userId)
    UNION ALL
    (SELECT 'Expense' AS type, (SELECT name FROM categories WHERE id = category_id) AS title, amount, date FROM expenses WHERE user_id = $userId)
    ORDER BY date DESC LIMIT 8
");

$chartData = $conn->query("SELECT c.name, SUM(e.amount) AS total FROM expenses e JOIN categories c ON c.id=e.category_id WHERE e.user_id = $userId GROUP BY c.name");
$labels = [];
$values = [];
while ($row = $chartData->fetch_assoc()) {
    $labels[] = $row["name"];
    $values[] = (float) $row["total"];
}

include "includes/header.php";
?>
<h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION["user_name"]) ?> 👋</h2>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h6>Total Income</h6><h4 class="text-success">Rs <?= number_format($totalIncome, 2) ?></h4></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h6>Total Expenses</h6><h4 class="text-danger">Rs <?= number_format($totalExpenses, 2) ?></h4></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h6>Remaining Balance</h6><h4 class="<?= $balance >= 0 ? "text-primary" : "text-danger" ?>">Rs <?= number_format($balance, 2) ?></h4></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><h6>Monthly Savings</h6><h4 class="<?= $monthlySavings >= 0 ? "text-success" : "text-danger" ?>">Rs <?= number_format($monthlySavings, 2) ?></h4></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Highest Spending Category</h5>
                <p class="mb-0 fw-semibold"><?= $highestCategory ? htmlspecialchars($highestCategory["name"]) . " (Rs " . number_format((float) $highestCategory["total"], 2) . ")" : "No expenses yet" ?></p>
            </div>
        </div>
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h5>Recent Transactions</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead><tr><th>Type</th><th>Title</th><th>Amount</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php while ($tx = $recentTx->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-<?= $tx["type"] === "Income" ? "success" : "danger" ?>"><?= htmlspecialchars($tx["type"]) ?></span></td>
                                <td><?= htmlspecialchars((string) $tx["title"]) ?></td>
                                <td>Rs <?= number_format((float) $tx["amount"], 2) ?></td>
                                <td><?= htmlspecialchars($tx["date"]) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h5>Category Expense Distribution</h5>
                <canvas id="expensePie"></canvas>
            </div>
        </div>
    </div>
</div>
<script>
new Chart(document.getElementById("expensePie"), {
    type: "pie",
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{ data: <?= json_encode($values) ?> }]
    }
});
</script>
<?php include "includes/footer.php"; ?>
