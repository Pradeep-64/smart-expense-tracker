<?php
declare(strict_types=1);

/** Platform-wide dashboard statistics using JOINs, aggregates, and subqueries. */
function adminDashboardStats(mysqli $conn): array
{
    $stats = [];

    $r = $conn->query('SELECT COUNT(*) AS c FROM users');
    $stats['total_users'] = (int) ($r->fetch_assoc()['c'] ?? 0);

    $r = $conn->query('SELECT COALESCE(SUM(amount),0) AS t FROM income');
    $stats['total_income'] = (float) ($r->fetch_assoc()['t'] ?? 0);

    $r = $conn->query('SELECT COALESCE(SUM(amount),0) AS t FROM expenses');
    $stats['total_expenses'] = (float) ($r->fetch_assoc()['t'] ?? 0);

    $stats['total_savings'] = $stats['total_income'] - $stats['total_expenses'];

    $r = $conn->query("
        SELECT u.name, COUNT(*) AS tx_count
        FROM users u
        INNER JOIN (
            SELECT user_id FROM expenses
            UNION ALL
            SELECT user_id FROM income
        ) t ON t.user_id = u.id
        GROUP BY u.id, u.name
        ORDER BY tx_count DESC
        LIMIT 1
    ");
    $row = $r->fetch_assoc();
    $stats['most_active_user'] = $row ? $row['name'] . ' (' . $row['tx_count'] . ' tx)' : 'N/A';

    $r = $conn->query('SELECT name, total_spent FROM top_spending_users LIMIT 1');
    $row = $r->fetch_assoc();
    $stats['highest_spending_user'] = $row
        ? $row['name'] . ' (' . formatMoney((float) $row['total_spent']) . ')'
        : 'N/A';

    $r = $conn->query('SELECT COUNT(*) AS c FROM expenses');
    $ec = (int) ($r->fetch_assoc()['c'] ?? 0);
    $r = $conn->query('SELECT COUNT(*) AS c FROM income');
    $ic = (int) ($r->fetch_assoc()['c'] ?? 0);
    $stats['total_transactions'] = $ec + $ic;

    $r = $conn->query("
        SELECT payment_method, COUNT(*) AS cnt
        FROM expenses
        GROUP BY payment_method
        ORDER BY cnt DESC
        LIMIT 1
    ");
    $row = $r->fetch_assoc();
    $stats['top_payment_method'] = $row ? $row['payment_method'] : 'N/A';

    $r = $conn->query("
        SELECT c.name, COUNT(e.id) AS cnt
        FROM categories c
        INNER JOIN expenses e ON e.category_id = c.id
        GROUP BY c.id, c.name
        ORDER BY cnt DESC
        LIMIT 1
    ");
    $row = $r->fetch_assoc();
    $stats['top_category'] = $row ? $row['name'] : 'N/A';

    return $stats;
}

function adminSmartInsights(mysqli $conn): array
{
    $insights = [];
    $month = date('Y-m');

    $r = $conn->query("
        SELECT c.name, COALESCE(SUM(e.amount),0) AS total
        FROM categories c
        INNER JOIN expenses e ON e.category_id = c.id
        WHERE DATE_FORMAT(e.date, '%Y-%m') = '$month'
        GROUP BY c.id, c.name
        ORDER BY total DESC
        LIMIT 1
    ");
    if ($row = $r->fetch_assoc()) {
        $insights[] = 'Users spend most on ' . $row['name'] . ' this month (' . formatMoney((float) $row['total']) . ').';
    }

    $r = $conn->query("
        SELECT c.name,
            COALESCE(SUM(CASE WHEN DATE_FORMAT(e.date,'%Y-%m') = '$month' THEN e.amount END),0) AS cur,
            COALESCE(SUM(CASE WHEN DATE_FORMAT(e.date,'%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH),'%Y-%m') THEN e.amount END),0) AS prev
        FROM categories c
        LEFT JOIN expenses e ON e.category_id = c.id
        WHERE c.name = 'Travel'
        GROUP BY c.name
    ");
    if ($row = $r->fetch_assoc()) {
        $prev = (float) $row['prev'];
        $cur = (float) $row['cur'];
        if ($prev > 0 && $cur > $prev) {
            $pct = round((($cur - $prev) / $prev) * 100);
            $insights[] = "Travel expenses increased by {$pct}% compared to last month.";
        }
    }

    $r = $conn->query("
        SELECT c.name, COALESCE(SUM(e.amount),0) AS total
        FROM categories c
        INNER JOIN expenses e ON e.category_id = c.id AND DATE_FORMAT(e.date,'%Y-%m') = '$month'
        GROUP BY c.id, c.name
        ORDER BY total DESC
        LIMIT 1
    ");
    if ($row = $r->fetch_assoc()) {
        $insights[] = $row['name'] . ' category dominates monthly spending platform-wide.';
    }

    $r = $conn->query("
        SELECT u.name, c.name AS cat, b.budget_amount,
            COALESCE(SUM(e.amount),0) AS spent
        FROM budgets b
        INNER JOIN users u ON u.id = b.user_id
        INNER JOIN categories c ON c.id = b.category_id
        LEFT JOIN expenses e ON e.user_id = b.user_id AND e.category_id = b.category_id
            AND DATE_FORMAT(e.date,'%Y-%m') = b.month_year
        WHERE b.month_year = '$month'
        GROUP BY u.name, c.name, b.budget_amount
        HAVING spent > b.budget_amount
        LIMIT 5
    ");
    while ($row = $r->fetch_assoc()) {
        $excess = (float) $row['spent'] - (float) $row['budget_amount'];
        $insights[] = 'User ' . $row['name'] . ' exceeded ' . $row['cat'] . ' budget by ' . formatMoney($excess) . '.';
    }

    if (empty($insights)) {
        $insights[] = 'Spending patterns are stable. No critical overspending detected this month.';
    }

    return array_unique($insights);
}

function chartUserGrowth(mysqli $conn): array
{
    $labels = [];
    $values = [];
    $r = $conn->query("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS cnt
        FROM users
        GROUP BY ym
        ORDER BY ym
    ");
    while ($row = $r->fetch_assoc()) {
        $labels[] = $row['ym'];
        $values[] = (int) $row['cnt'];
    }
    return ['labels' => $labels, 'values' => $values];
}

function chartExpenseDistribution(mysqli $conn): array
{
    $labels = [];
    $values = [];
    $r = $conn->query('SELECT category_name, total_amount FROM category_wise_expenses ORDER BY total_amount DESC');
    while ($row = $r->fetch_assoc()) {
        if ((float) $row['total_amount'] > 0) {
            $labels[] = $row['category_name'];
            $values[] = (float) $row['total_amount'];
        }
    }
    return ['labels' => $labels, 'values' => $values];
}

function chartMonthlyTransactions(mysqli $conn): array
{
    $labels = [];
    $expenses = [];
    $income = [];
    $r = $conn->query("
        SELECT m.month_year,
            COALESCE(SUM(e.amount),0) AS exp_total,
            COALESCE(SUM(i.amount),0) AS inc_total
        FROM (
            SELECT DISTINCT DATE_FORMAT(date,'%Y-%m') AS month_year FROM expenses
            UNION
            SELECT DISTINCT DATE_FORMAT(date,'%Y-%m') FROM income
        ) m
        LEFT JOIN expenses e ON DATE_FORMAT(e.date,'%Y-%m') = m.month_year
        LEFT JOIN income i ON DATE_FORMAT(i.date,'%Y-%m') = m.month_year
        GROUP BY m.month_year
        ORDER BY m.month_year
        LIMIT 12
    ");
    while ($row = $r->fetch_assoc()) {
        $labels[] = $row['month_year'];
        $expenses[] = (float) $row['exp_total'];
        $income[] = (float) $row['inc_total'];
    }
    return ['labels' => $labels, 'expenses' => $expenses, 'income' => $income];
}

function chartTopCategories(mysqli $conn): array
{
    $labels = [];
    $values = [];
    $r = $conn->query('SELECT category_name, total_amount FROM category_wise_expenses ORDER BY total_amount DESC LIMIT 8');
    while ($row = $r->fetch_assoc()) {
        $labels[] = $row['category_name'];
        $values[] = (float) $row['total_amount'];
    }
    return ['labels' => $labels, 'values' => $values];
}

function chartActiveUsers(mysqli $conn): array
{
    $labels = [];
    $values = [];
    $r = $conn->query("
        SELECT u.name, COUNT(e.id) AS cnt
        FROM users u
        INNER JOIN expenses e ON e.user_id = u.id
        GROUP BY u.id, u.name
        ORDER BY cnt DESC
        LIMIT 8
    ");
    while ($row = $r->fetch_assoc()) {
        $labels[] = $row['name'];
        $values[] = (int) $row['cnt'];
    }
    return ['labels' => $labels, 'values' => $values];
}

function topSpendingUsers(mysqli $conn, int $limit = 5): array
{
    $rows = [];
    $stmt = $conn->prepare('SELECT user_id, name, email, total_spent FROM top_spending_users LIMIT ?');
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}
