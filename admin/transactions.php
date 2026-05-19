<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$payment = trim($_GET['payment'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$minAmt = $_GET['min_amount'] ?? '';
$maxAmt = $_GET['max_amount'] ?? '';
$type = $_GET['type'] ?? 'expense';

$sql = '';
$params = [];
$types = '';

if ($type === 'income') {
    $sql = "SELECT i.id, u.name AS user_name, 'Income' AS tx_type, i.source AS title,
            i.amount, '' AS payment_method, i.date, i.description
            FROM income i INNER JOIN users u ON u.id = i.user_id WHERE 1=1";
    if ($q !== '') {
        $sql .= ' AND (u.name LIKE ? OR i.source LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }
} else {
    $sql = "SELECT e.id, u.name AS user_name, 'Expense' AS tx_type, c.name AS title,
            e.amount, e.payment_method, e.date, e.description
            FROM expenses e
            INNER JOIN users u ON u.id = e.user_id
            INNER JOIN categories c ON c.id = e.category_id
            WHERE 1=1";
    if ($q !== '') {
        $sql .= ' AND (u.name LIKE ? OR c.name LIKE ? OR e.description LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    if ($category !== '') {
        $sql .= ' AND c.name = ?';
        $params[] = $category;
        $types .= 's';
    }
    if ($payment !== '') {
        $sql .= ' AND e.payment_method = ?';
        $params[] = $payment;
        $types .= 's';
    }
}

if ($dateFrom !== '') {
    $sql .= ' AND ' . ($type === 'income' ? 'i' : 'e') . '.date >= ?';
    $params[] = $dateFrom;
    $types .= 's';
}
if ($dateTo !== '') {
    $sql .= ' AND ' . ($type === 'income' ? 'i' : 'e') . '.date <= ?';
    $params[] = $dateTo;
    $types .= 's';
}
if ($minAmt !== '') {
    $sql .= ' AND ' . ($type === 'income' ? 'i' : 'e') . '.amount >= ?';
    $params[] = (float) $minAmt;
    $types .= 'd';
}
if ($maxAmt !== '') {
    $sql .= ' AND ' . ($type === 'income' ? 'i' : 'e') . '.amount <= ?';
    $params[] = (float) $maxAmt;
    $types .= 'd';
}

$sql .= ' ORDER BY date DESC LIMIT 200';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result();

$categories = $conn->query('SELECT name FROM categories ORDER BY name');

$pageTitle = 'Transaction Search';
$pageSubtitle = 'Filter by user, category, payment, date and amount range';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/topbar.php';
?>

<div class="card admin-card mb-4 no-print">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>Expenses</option>
                    <option value="income" <?= $type === 'income' ? 'selected' : '' ?>>Income</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="User, category, description">
            </div>
            <?php if ($type === 'expense'): ?>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All</option>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?= e($c['name']) ?>" <?= $category === $c['name'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Payment</label>
                <select name="payment" class="form-select">
                    <option value="">All</option>
                    <?php foreach (['Cash', 'UPI', 'Debit Card', 'Credit Card', 'Net Banking'] as $pm): ?>
                        <option value="<?= $pm ?>" <?= $payment === $pm ? 'selected' : '' ?>><?= $pm ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>"></div>
            <div class="col-md-1"><label class="form-label">Min</label><input type="number" step="0.01" name="min_amount" class="form-control" value="<?= e($minAmt) ?>"></div>
            <div class="col-md-1"><label class="form-label">Max</label><input type="number" step="0.01" name="max_amount" class="form-control" value="<?= e($maxAmt) ?>"></div>
            <div class="col-md-2 align-self-end"><button class="btn btn-primary w-100">Search</button></div>
        </form>
    </div>
</div>

<div class="card admin-card">
    <div class="card-header d-flex justify-content-between">
        <span>Results (<?= $rows->num_rows ?>)</span>
        <div class="no-print">
            <a href="export.php?type=transactions&amp;<?= e(http_build_query($_GET)) ?>" class="btn btn-sm btn-outline-success">Excel</a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printReport()">Print</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>User</th><th>Type</th><th>Title</th><th>Amount</th><th>Payment</th><th>Date</th><th>Description</th></tr></thead>
            <tbody>
            <?php while ($r = $rows->fetch_assoc()): ?>
                <tr>
                    <td><?= e($r['user_name']) ?></td>
                    <td><?= e($r['tx_type']) ?></td>
                    <td><?= e($r['title']) ?></td>
                    <td><?= formatMoney((float) $r['amount']) ?></td>
                    <td><?= e($r['payment_method'] ?: '-') ?></td>
                    <td><?= e($r['date']) ?></td>
                    <td><?= e($r['description'] ?? '') ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
