<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

if (isset($_GET['toggle_block'])) {
    $uid = (int) $_GET['toggle_block'];
    $stmt = $conn->prepare('UPDATE users SET is_blocked = IF(is_blocked = 1, 0, 1) WHERE id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    header('Location: users.php');
    exit;
}

if (isset($_GET['delete'])) {
    $uid = (int) $_GET['delete'];
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    header('Location: users.php?deleted=1');
    exit;
}

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$sql = 'SELECT * FROM user_financial_summary WHERE 1=1';
$params = [];
$types = '';

if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if ($status === 'blocked') {
    $sql .= ' AND is_blocked = 1';
} elseif ($status === 'active') {
    $sql .= ' AND is_blocked = 0';
}
$sql .= ' ORDER BY total_expenses DESC';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

$pageTitle = 'User Management';
$pageSubtitle = 'Search, filter, monitor and manage registered users';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/topbar.php';
?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">User deleted successfully (cascading delete applied).</div>
<?php endif; ?>

<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end no-print">
            <div class="col-md-5">
                <label class="form-label">Search</label>
                <input type="text" name="q" class="form-control" placeholder="Name or email" value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="users.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card admin-card">
    <div class="card-header d-flex justify-content-between">
        <span>All Users</span>
        <a href="export.php?type=users" class="btn btn-sm btn-outline-primary no-print">Export Excel</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Income</th><th>Expenses</th>
                    <th>Savings</th><th>Transactions</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= (int) $u['user_id'] ?></td>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= formatMoney((float) $u['total_income']) ?></td>
                    <td><?= formatMoney((float) $u['total_expenses']) ?></td>
                    <td><?= formatMoney((float) $u['total_savings']) ?></td>
                    <td><?= (int) $u['total_transactions'] ?></td>
                    <td>
                        <?php if ((int) $u['is_blocked'] === 1): ?>
                            <span class="badge bg-danger">Blocked</span>
                        <?php else: ?>
                            <span class="badge bg-success">Active</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="user_view.php?id=<?= (int) $u['user_id'] ?>">View</a>
                        <a class="btn btn-sm btn-outline-warning" href="users.php?toggle_block=<?= (int) $u['user_id'] ?>"
                           onclick="return confirm('Toggle block status?')"><?= (int) $u['is_blocked'] ? 'Unblock' : 'Block' ?></a>
                        <a class="btn btn-sm btn-outline-danger" href="users.php?delete=<?= (int) $u['user_id'] ?>"
                           onclick="return confirm('Delete user and all related data?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
