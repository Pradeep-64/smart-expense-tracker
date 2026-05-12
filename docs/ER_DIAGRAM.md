# ER Diagram Explanation

## Entities

- `users`: stores registered user details and login credentials.
- `categories`: master list of expense categories.
- `income`: all income records for each user.
- `expenses`: all expense records tagged with category and payment method.
- `budgets`: monthly budget allocation by user and category.
- `budget_alerts`: auto-generated warnings when category spend exceeds monthly budget.

## Relationships

- One `user` -> many `income` entries (`users.id` -> `income.user_id`).
- One `user` -> many `expenses` entries (`users.id` -> `expenses.user_id`).
- One `category` -> many `expenses` entries (`categories.id` -> `expenses.category_id`).
- One `user` + one `category` + one `month` -> one `budget` row (unique composite key in `budgets`).
- One `budget` can produce many `budget_alerts` when overspending happens.

## DBMS Concepts Used

- Primary keys in all tables.
- Foreign keys with referential integrity.
- `JOIN`, `GROUP BY`, and aggregate functions (`SUM`, `COALESCE`) across dashboards and reports.
- Stored procedure `GetMonthlySummary` for reusable monthly aggregate.
- Trigger `trg_budget_alert_after_expense` for budget warning automation.
