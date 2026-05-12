<?php
declare(strict_types=1);
require_once "config/db.php";
require_once "includes/auth.php";
requireLogin();
$userId = (int) $_SESSION["user_id"];

$daily = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE user_id=$userId AND date=CURDATE()")->fetch_assoc()["total"];
$weekly = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE user_id=$userId AND YEARWEEK(date,1)=YEARWEEK(CURDATE(),1)")->fetch_assoc()["total"];
$monthly = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE user_id=$userId AND DATE_FORMAT(date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')")->fetch_assoc()["total"];
$yearly = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE user_id=$userId AND YEAR(date)=YEAR(CURDATE())")->fetch_assoc()["total"];

$catRows = $conn->query("SELECT c.name, COALESCE(SUM(e.amount),0) AS total FROM categories c LEFT JOIN expenses e ON c.id=e.category_id AND e.user_id=$userId GROUP BY c.id, c.name");
$catLabels = [];
$catTotals = [];
while ($r = $catRows->fetch_assoc()) {
    $catLabels[] = $r["name"];
    $catTotals[] = (float) $r["total"];
}

$monthRows = $conn->query("SELECT DATE_FORMAT(date,'%b') AS month_name, MONTH(date) AS m, SUM(amount) AS total FROM expenses WHERE user_id=$userId AND YEAR(date)=YEAR(CURDATE()) GROUP BY m, month_name ORDER BY m");
$mLabels = [];
$mValues = [];
while ($m = $monthRows->fetch_assoc()) {
    $mLabels[] = $m["month_name"];
    $mValues[] = (float) $m["total"];
}

$incExpRows = $conn->query("
    SELECT t.mon,
        SUM(t.income_amount) AS income,
        SUM(t.expense_amount) AS expense
    FROM (
        SELECT DATE_FORMAT(date,'%b') AS mon, MONTH(date) AS m, amount AS income_amount, 0 AS expense_amount FROM income WHERE user_id=$userId AND YEAR(date)=YEAR(CURDATE())
        UNION ALL
        SELECT DATE_FORMAT(date,'%b') AS mon, MONTH(date) AS m, 0 AS income_amount, amount AS expense_amount FROM expenses WHERE user_id=$userId AND YEAR(date)=YEAR(CURDATE())
    ) t
    GROUP BY t.m, t.mon
    ORDER BY t.m
");
$ieLabels = [];
$incomeVals = [];
$expenseVals = [];
while ($x = $incExpRows->fetch_assoc()) {
    $ieLabels[] = $x["mon"];
    $incomeVals[] = (float) $x["income"];
    $expenseVals[] = (float) $x["expense"];
}

$foodCurrent = $conn->query("SELECT COALESCE(SUM(e.amount),0) AS total FROM expenses e JOIN categories c ON c.id=e.category_id WHERE e.user_id=$userId AND c.name='Food' AND DATE_FORMAT(e.date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')")->fetch_assoc()["total"];
$foodPrev = $conn->query("SELECT COALESCE(SUM(e.amount),0) AS total FROM expenses e JOIN categories c ON c.id=e.category_id WHERE e.user_id=$userId AND c.name='Food' AND DATE_FORMAT(e.date,'%Y-%m')=DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH),'%Y-%m')")->fetch_assoc()["total"];

$smartSuggestion = "Your spending pattern looks stable this month. Keep tracking consistently.";
if ((float) $foodPrev > 0 && (float) $foodCurrent > (float) $foodPrev) {
    $increase = (((float) $foodCurrent - (float) $foodPrev) / (float) $foodPrev) * 100;
    $smartSuggestion = "You spent " . number_format($increase, 0) . "% more on food this month. Try meal planning to reduce costs.";
}
if ((float) $monthly > 0) {
    $shopping = $conn->query("SELECT COALESCE(SUM(e.amount),0) AS total FROM expenses e JOIN categories c ON c.id=e.category_id WHERE e.user_id=$userId AND c.name='Shopping' AND DATE_FORMAT(e.date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')")->fetch_assoc()["total"];
    if ((float) $shopping > 0 && ((float) $shopping / (float) $monthly) > 0.25) {
        $smartSuggestion = "Shopping expenses are above 25% of this month's spend. Reduce shopping expenses to save more.";
    }
}

include "includes/header.php";
?>
<h3 class="mb-4">Reports & Analytics</h3>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><h6>Daily</h6><h5>Rs <?= number_format((float) $daily, 2) ?></h5></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><h6>Weekly</h6><h5>Rs <?= number_format((float) $weekly, 2) ?></h5></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><h6>Monthly</h6><h5>Rs <?= number_format((float) $monthly, 2) ?></h5></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><h6>Yearly</h6><h5>Rs <?= number_format((float) $yearly, 2) ?></h5></div></div></div>
</div>
<div class="alert alert-info"><strong>Smart Suggestion:</strong> <?= htmlspecialchars($smartSuggestion) ?></div>

<div class="row g-4">
    <div class="col-lg-4"><div class="card"><div class="card-body"><h6>Category-wise Expenses (Pie)</h6><canvas id="pieChart"></canvas></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body"><h6>Monthly Spending (Bar)</h6><canvas id="barChart"></canvas></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body"><h6>Income vs Expense (Line)</h6><canvas id="compareChart"></canvas></div></div></div>
</div>

<script>
new Chart(document.getElementById("pieChart"), {
    type: "pie",
    data: { labels: <?= json_encode($catLabels) ?>, datasets: [{ data: <?= json_encode($catTotals) ?> }] }
});
new Chart(document.getElementById("barChart"), {
    type: "bar",
    data: { labels: <?= json_encode($mLabels) ?>, datasets: [{ label: "Monthly Spending", data: <?= json_encode($mValues) ?>, backgroundColor: "#0d6efd" }] }
});
new Chart(document.getElementById("compareChart"), {
    type: "line",
    data: {
        labels: <?= json_encode($ieLabels) ?>,
        datasets: [
            { label: "Income", data: <?= json_encode($incomeVals) ?>, borderColor: "#198754", tension: 0.3 },
            { label: "Expense", data: <?= json_encode($expenseVals) ?>, borderColor: "#dc3545", tension: 0.3 }
        ]
    }
});
</script>
<?php include "includes/footer.php"; ?>
