<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$userId = (int) ($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: users.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM user_financial_summary WHERE user_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    header('Location: users.php');
    exit;
}

$month = $_GET['month'] ?? date('Y-m');
$reportStmt = $conn->prepare('CALL GetMonthlySummary(?, ?)');
$reportStmt->bind_param('is', $userId, $month);
$reportStmt->execute();
$monthReport = $reportStmt->get_result()->fetch_assoc();
$conn->next_result();

$incomeStmt = $conn->prepare('SELECT * FROM income WHERE user_id = ? ORDER BY date DESC LIMIT 20');
$incomeStmt->bind_param('i', $userId);
$incomeStmt->execute();
$incomes = $incomeStmt->get_result();

$expenseStmt = $conn->prepare('
    SELECT e.*, c.name AS category_name FROM expenses e
    INNER JOIN categories c ON c.id = e.category_id
    WHERE e.user_id = ? ORDER BY e.date DESC LIMIT 20
');
$expenseStmt->bind_param('i', $userId);
$expenseStmt->execute();
$expenses = $expenseStmt->get_result();

$overspendStmt = $conn->prepare('CALL sp_detect_overspending(?, ?)');
$overspendStmt->bind_param('is', $userId, $month);
$overspendStmt->execute();
$overspendData = $overspendStmt->get_result();
$conn->next_result();

$pageTitle = 'User Profile: ' . $user['name'];
$pageSubtitle = $user['email'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/topbar.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card admin-stat-card"><div class="card-body"><h6>Total Income</h6><h4><?= formatMoney((float) $user['total_income']) ?></h4></div></div></div>
    <div class="col-md-3"><div class="card admin-stat-card"><div class="card-body"><h6>Total Expenses</h6><h4><?= formatMoney((float) $user['total_expenses']) ?></h4></div></div></div>
    <div class="col-md-3"><div class="card admin-stat-card"><div class="card-body"><h6>Savings</h6><h4><?= formatMoney((float) $user['total_savings']) ?></h4></div></div></div>
    <div class="col-md-3"><div class="card admin-stat-card"><div class="card-body"><h6>Status</h6><h4><?= (int) $user['is_blocked'] ? 'Blocked' : 'Active' ?></h4></div></div></div>
</div>

<div class="card admin-card mb-4">
    <div class="card-header">Monthly Report (<?= e($month) ?>)</div>
    <div class="card-body">
        <form method="get" class="row g-2 mb-3 no-print">
            <input type="hidden" name="id" value="<?= $userId ?>">
            <div class="col-auto"><input type="month" name="month" class="form-control" value="<?= e($month) ?>"></div>
            <div class="col-auto"><button class="btn btn-primary btn-sm">Load</button></div>
        </form>
        <?php if ($monthReport): ?>
        <p>Month Income: <strong><?= formatMoney((float) $monthReport['total_income']) ?></strong></p>
        <p>Month Expense: <strong><?= formatMoney((float) $monthReport['total_expense']) ?></strong></p>
        <p>Month Savings: <strong><?= formatMoney((float) $monthReport['total_income'] - (float) $monthReport['total_expense']) ?></strong></p>
        <?php endif; ?>
        <?php if ($overspendData->num_rows > 0): ?>
        <hr><h6 class="text-danger">Overspending detected</h6>
        <ul><?php while ($o = $overspendData->fetch_assoc()): ?>
            <li><?= e($o['category_name']) ?>: exceeded by <?= formatMoney((float) $o['exceeded_by']) ?></li>
        <?php endwhile; ?></ul>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card admin-card">
            <div class="card-header">Income History</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Source</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php while ($r = $incomes->fetch_assoc()): ?>
                        <tr><td><?= e($r['source']) ?></td><td><?= formatMoney((float) $r['amount']) ?></td><td><?= e($r['date']) ?></td></tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card admin-card">
            <div class="card-header">Expense History</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Category</th><th>Amount</th><th>Payment</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php while ($r = $expenses->fetch_assoc()): ?>
                        <tr>
                            <td><?= e($r['category_name']) ?></td>
                            <td><?= formatMoney((float) $r['amount']) ?></td>
                            <td><?= e($r['payment_method']) ?></td>
                            <td><?= e($r['date']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<p class="mt-3"><a href="users.php" class="btn btn-secondary">Back to users</a></p>
<?php include __DIR__ . '/includes/footer.php'; ?>
