<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/includes/analytics.php';
requireAdminLogin();

$stats = adminDashboardStats($conn);
$insights = adminSmartInsights($conn);
$userGrowth = chartUserGrowth($conn);
$expDist = chartExpenseDistribution($conn);
$monthly = chartMonthlyTransactions($conn);
$topCats = chartTopCategories($conn);
$activeUsers = chartActiveUsers($conn);

include __DIR__ . '/includes/header.php';
?>
<div class="admin-topbar">
    <div>
        <h2 class="mb-0">Admin Dashboard</h2>
        <p class="text-muted mb-0">Platform analytics and monitoring</p>
    </div>
    <div class="d-flex gap-2 no-print">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <button type="button" class="btn btn-outline-dark btn-sm" id="themeToggle"><i class="bi bi-moon-stars"></i></button>
        <span class="align-self-center small">Hi, <?= e($_SESSION['admin_name']) ?></span>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Total Users', (string) $stats['total_users'], 'people', 'indigo'],
        ['Total Income', formatMoney($stats['total_income']), 'cash-stack', 'emerald'],
        ['Total Expenses', formatMoney($stats['total_expenses']), 'cart', 'rose'],
        ['Total Savings', formatMoney($stats['total_savings']), 'piggy-bank', 'sky'],
        ['Most Active User', $stats['most_active_user'], 'lightning', 'amber'],
        ['Highest Spender', $stats['highest_spending_user'], 'trophy', 'rose'],
        ['Total Transactions', (string) $stats['total_transactions'], 'receipt', 'indigo'],
        ['Top Payment Method', $stats['top_payment_method'], 'credit-card', 'emerald'],
        ['Top Category', $stats['top_category'], 'tags', 'amber'],
    ];
    foreach ($cards as $i => $c): ?>
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card admin-stat-card animate-count">
            <div class="card-body d-flex gap-3 align-items-center">
                <div class="icon-wrap bg-<?= $c[3] ?>"><i class="bi bi-<?= $c[2] ?>"></i></div>
                <div>
                    <h6><?= e($c[0]) ?></h6>
                    <h3 class="fs-5"><?= e((string) $c[1]) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card admin-card h-100">
            <div class="card-header">Smart Insights</div>
            <div class="card-body">
                <?php foreach ($insights as $ins): ?>
                    <div class="insight-item"><?= e($ins) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card admin-card h-100">
            <div class="card-header">User Growth</div>
            <div class="card-body"><canvas id="userGrowthChart" height="200"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card admin-card">
            <div class="card-header">Expense Distribution</div>
            <div class="card-body"><canvas id="pieChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card admin-card">
            <div class="card-header">Monthly Transactions</div>
            <div class="card-body"><canvas id="barChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card admin-card">
            <div class="card-header">Income vs Expense</div>
            <div class="card-body"><canvas id="lineChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card admin-card">
            <div class="card-header">Top Spending Categories</div>
            <div class="card-body"><canvas id="catChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card admin-card">
            <div class="card-header">Most Active Users
            <div class="card-body"><canvas id="activeChart"></canvas></div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('userGrowthChart'), {
    type: 'line',
    data: { labels: <?= json_encode($userGrowth['labels']) ?>, datasets: [{ label: 'New Users', data: <?= json_encode($userGrowth['values']) ?>, borderColor: '#6366f1', tension: 0.3, fill: true, backgroundColor: 'rgba(99,102,241,0.1)' }] }
});
new Chart(document.getElementById('pieChart'), { type: 'pie', data: { labels: <?= json_encode($expDist['labels']) ?>, datasets: [{ data: <?= json_encode($expDist['values']) ?> }] } });
new Chart(document.getElementById('barChart'), { type: 'bar', data: { labels: <?= json_encode($monthly['labels']) ?>, datasets: [{ label: 'Expenses', data: <?= json_encode($monthly['expenses']) ?>, backgroundColor: '#f43f5e' }] } });
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($monthly['labels']) ?>,
        datasets: [
            { label: 'Income', data: <?= json_encode($monthly['income']) ?>, borderColor: '#10b981', tension: 0.3 },
            { label: 'Expenses', data: <?= json_encode($monthly['expenses']) ?>, borderColor: '#f43f5e', tension: 0.3 }
        ]
    }
});
new Chart(document.getElementById('catChart'), { type: 'bar', data: { labels: <?= json_encode($topCats['labels']) ?>, datasets: [{ data: <?= json_encode($topCats['values']) ?>, backgroundColor: '#6366f1' }] } });
new Chart(document.getElementById('activeChart'), { type: 'bar', data: { labels: <?= json_encode($activeUsers['labels']) ?>, datasets: [{ data: <?= json_encode($activeUsers['values']) ?>, backgroundColor: '#0ea5e9' }] }, options: { indexAxis: 'y' } });
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
