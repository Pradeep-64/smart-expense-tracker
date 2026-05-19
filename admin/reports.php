<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/includes/analytics.php';
requireAdminLogin();

$month = $_GET['month'] ?? date('Y-m');

$topUsers = topSpendingUsers($conn, 5);

$catAnalysis = $conn->prepare('CALL sp_category_analysis(?)');
$catAnalysis->bind_param('s', $month);
$catAnalysis->execute();
$catRows = $catAnalysis->get_result();
$conn->next_result();

$monthlyReport = $conn->prepare('CALL sp_generate_monthly_report(?)');
$monthlyReport->bind_param('s', $month);
$monthlyReport->execute();
$platformMonthly = $monthlyReport->get_result();
$conn->next_result();

$savingsRes = $conn->query('CALL sp_calculate_total_savings()');
$savings = $savingsRes->fetch_assoc();
$conn->next_result();

$growthSql = "
    SELECT DATE_FORMAT(e.date, '%Y-%m') AS ym,
        SUM(e.amount) AS expense_total,
        (SELECT COALESCE(SUM(i.amount),0) FROM income i WHERE DATE_FORMAT(i.date,'%Y-%m') = DATE_FORMAT(e.date,'%Y-%m')) AS income_total
    FROM expenses e
    GROUP BY ym
    ORDER BY ym DESC
    LIMIT 6
";
$growth = $conn->query($growthSql);
$gLabels = [];
$gExpense = [];
$gIncome = [];
while ($g = $growth->fetch_assoc()) {
    $gLabels[] = $g['ym'];
    $gExpense[] = (float) $g['expense_total'];
    $gIncome[] = (float) $g['income_total'];
}
$gLabels = array_reverse($gLabels);
$gExpense = array_reverse($gExpense);
$gIncome = array_reverse($gIncome);

$payAnalysis = $conn->query("
    SELECT payment_method, COUNT(*) AS cnt, SUM(amount) AS total
    FROM expenses
    WHERE DATE_FORMAT(date, '%Y-%m') = '$month'
    GROUP BY payment_method
    ORDER BY total DESC
");

$pageTitle = 'Advanced Analytics';
$pageSubtitle = 'Financial reports with SQL procedures, views and aggregates';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/topbar.php';
?>

<div class="d-flex gap-2 mb-3 no-print flex-wrap">
    <form method="get" class="d-flex gap-2">
        <input type="month" name="month" class="form-control form-control-sm" value="<?= e($month) ?>">
        <button class="btn btn-sm btn-primary">Apply</button>
    </form>
    <a href="export.php?type=analytics&amp;month=<?= e($month) ?>" class="btn btn-sm btn-outline-success">Export Excel</a>
    <a href="export.php?type=analytics&amp;month=<?= e($month) ?>&amp;format=csv" class="btn btn-sm btn-outline-primary">Export CSV</a>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printReport()">Print / PDF</button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card admin-stat-card"><div class="card-body"><h6>Platform Income</h6><h4><?= formatMoney((float) ($savings['platform_total_income'] ?? 0)) ?></h4></div></div></div>
    <div class="col-md-4"><div class="card admin-stat-card"><div class="card-body"><h6>Platform Expenses</h6><h4><?= formatMoney((float) ($savings['platform_total_expenses'] ?? 0)) ?></h4></div></div></div>
    <div class="col-md-4"><div class="card admin-stat-card"><div class="card-body"><h6>Platform Savings</h6><h4><?= formatMoney((float) ($savings['platform_total_savings'] ?? 0)) ?></h4></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card admin-card">
            <div class="card-header">Top 5 Highest Spending Users (VIEW)</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Total Spent</th></tr></thead>
                    <tbody>
                    <?php $i = 1; foreach ($topUsers as $tu): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= e($tu['name']) ?></td>
                            <td><?= e($tu['email']) ?></td>
                            <td><?= formatMoney((float) $tu['total_spent']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card admin-card">
            <div class="card-header">Category Analysis — <?= e($month) ?> (PROCEDURE)</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Category</th><th>Count</th><th>Total</th><th>Avg</th></tr></thead>
                    <tbody>
                    <?php while ($c = $catRows->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($c['category_name']) ?></td>
                            <td><?= (int) $c['tx_count'] ?></td>
                            <td><?= formatMoney((float) $c['total_spent']) ?></td>
                            <td><?= formatMoney((float) $c['avg_spent']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card admin-card">
            <div class="card-header">Income vs Expense Trend (Subquery)</div>
            <div class="card-body"><canvas id="trendChart" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card admin-card">
            <div class="card-header">Payment Methods — <?= e($month) ?></div>
            <div class="card-body"><canvas id="payChart"></canvas></div>
        </div>
    </div>
</div>

<div class="card admin-card">
    <div class="card-header">Monthly User Report — <?= e($month) ?> (sp_generate_monthly_report)</div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>User</th><th>Income</th><th>Expense</th><th>Savings</th></tr></thead>
            <tbody>
            <?php while ($m = $platformMonthly->fetch_assoc()): ?>
                <tr>
                    <td><?= e($m['name']) ?></td>
                    <td><?= formatMoney((float) $m['month_income']) ?></td>
                    <td><?= formatMoney((float) $m['month_expense']) ?></td>
                    <td><?= formatMoney((float) $m['month_savings']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$payLabels = [];
$payValues = [];
$payAnalysis->data_seek(0);
while ($p = $payAnalysis->fetch_assoc()) {
    $payLabels[] = $p['payment_method'];
    $payValues[] = (float) $p['total'];
}
?>
<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($gLabels) ?>,
        datasets: [
            { label: 'Income', data: <?= json_encode($gIncome) ?>, borderColor: '#10b981', tension: 0.3 },
            { label: 'Expense', data: <?= json_encode($gExpense) ?>, borderColor: '#f43f5e', tension: 0.3 }
        ]
    }
});
new Chart(document.getElementById('payChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode($payLabels) ?>, datasets: [{ data: <?= json_encode($payValues) ?> }] }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
