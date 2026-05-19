<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$search = trim($_GET['q'] ?? '');
$sql = 'SELECT a.log_id, a.activity, a.created_at, u.name, u.email
        FROM activity_logs a
        INNER JOIN users u ON u.id = a.user_id
        WHERE 1=1';
$params = [];
$types = '';
if ($search !== '') {
    $sql .= ' AND (u.name LIKE ? OR a.activity LIKE ?)';
    $like = '%' . $search . '%';
    $params = [$like, $like];
    $types = 'ss';
}
$sql .= ' ORDER BY a.created_at DESC LIMIT 150';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();

$pageTitle = 'Activity Logs';
$pageSubtitle = 'Auto-logged via database triggers on income and expenses';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/topbar.php';
?>

<div class="card admin-card mb-3 no-print">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-8"><input type="text" name="q" class="form-control" placeholder="Search user or activity" value="<?= e($search) ?>"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Search</button></div>
        </form>
    </div>
</div>

<div class="card admin-card">
    <div class="card-header">Recent Activity (trigger: trg_expense_activity_log, trg_income_activity_log)</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>User</th><th>Activity</th><th>Time</th></tr></thead>
            <tbody>
            <?php while ($l = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?= (int) $l['log_id'] ?></td>
                    <td><?= e($l['name']) ?> <small class="text-muted"><?= e($l['email']) ?></small></td>
                    <td><?= e($l['activity']) ?></td>
                    <td><?= e($l['created_at']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
