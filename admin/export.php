<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../reports/export_helper.php';
requireAdminLogin();

$type = $_GET['type'] ?? 'users';
$format = $_GET['format'] ?? 'excel';

if ($type === 'users') {
    $res = $conn->query('SELECT user_id, name, email, total_income, total_expenses, total_savings, total_transactions, is_blocked FROM user_financial_summary ORDER BY total_expenses DESC');
    $headers = ['ID', 'Name', 'Email', 'Income', 'Expenses', 'Savings', 'Transactions', 'Blocked'];
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [
            $r['user_id'], $r['name'], $r['email'], $r['total_income'], $r['total_expenses'],
            $r['total_savings'], $r['total_transactions'], $r['is_blocked'] ? 'Yes' : 'No',
        ];
    }
    if ($format === 'csv') {
        exportCsv('users_report.csv', $headers, $rows);
    }
    exportExcelCsv('users_report', $headers, $rows);
}

if ($type === 'analytics') {
    $month = $_GET['month'] ?? date('Y-m');
    $stmt = $conn->prepare('CALL sp_generate_monthly_report(?)');
    $stmt->bind_param('s', $month);
    $stmt->execute();
    $res = $stmt->get_result();
    $headers = ['User', 'Month Income', 'Month Expense', 'Month Savings'];
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [$r['name'], $r['month_income'], $r['month_expense'], $r['month_savings']];
    }
    if ($format === 'csv') {
        exportCsv("analytics_{$month}.csv", $headers, $rows);
    }
    exportExcelCsv("analytics_{$month}", $headers, $rows);
}

header('Location: reports.php');
exit;
