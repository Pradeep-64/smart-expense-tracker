# Smart Expense Tracker with Budget Analysis and Spending Insights

A professional PHP + MySQL full-stack project for personal finance tracking, budget alerts, user analytics, and a complete **Admin Management & Advanced SQL Analytics System**.

## Tech Stack

- PHP 8+
- MySQL / MariaDB
- HTML5, CSS3, JavaScript
- Bootstrap 5
- Chart.js
- XAMPP (Apache + MySQL)

## Project Structure

```text
smart-expense-tracker/
├── admin/                    # Admin panel (RBAC, analytics, monitoring)
│   ├── includes/
│   ├── dashboard.php
│   ├── users.php
│   ├── user_view.php
│   ├── transactions.php
│   ├── reports.php
│   ├── activity.php
│   ├── export.php
│   ├── login.php
│   └── logout.php
├── assets/
│   ├── css/ (style.css, admin.css)
│   └── js/ (app.js, admin.js)
├── config/
│   └── db.php
├── database/
│   └── expense_tracker.sql
├── docs/
│   └── ER_DIAGRAM.md
├── includes/
│   ├── auth.php
│   ├── admin_auth.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── reports/
│   └── export_helper.php
├── index.php                 # User entry
├── dashboard.php             # User module pages
└── ...
```

## User Features

- Registration, login, logout (password hashing, sessions)
- Income & expense CRUD with filters
- Budget management with overspending alerts
- Reports with Chart.js (pie, bar, line)
- Smart spending suggestions

## Admin Features

- Secure admin login & session timeout (1 hour)
- Dashboard with 9 KPI cards + 6 Chart.js visualizations
- User management: search, filter, block/unblock, delete, profile view
- Transaction search across all users
- Advanced SQL analytics (views, procedures, triggers)
- Smart insights (category trends, overspending)
- Activity logs (auto via triggers)
- Export CSV / Excel, print-friendly PDF via browser

## DBMS Concepts Demonstrated

| Concept | Implementation |
|--------|----------------|
| Normalization | 3NF tables: users, categories, income, expenses, budgets |
| JOINs | INNER JOIN, LEFT JOIN in reports and admin queries |
| Aggregates | SUM, COUNT, AVG, GROUP BY, HAVING |
| Subqueries | Dashboard savings, trend analysis |
| Views | `monthly_expense_summary`, `category_wise_expenses`, `user_financial_summary`, `top_spending_users` |
| Stored Procedures | `sp_generate_monthly_report`, `sp_calculate_total_savings`, `sp_category_analysis`, `sp_detect_overspending`, `GetMonthlySummary` |
| Triggers | Budget alerts, notifications, activity logs, monthly_totals auto-update |
| Indexes | user_id+date, category_id, payment_method, email |
| Referential Integrity | Foreign keys with ON DELETE CASCADE |
| Constraints | UNIQUE email, CHECK amount >= 0, ENUM types |
| Transactions | Multi-table cascade deletes |

## Setup (XAMPP)

1. Copy project folder to `C:\xampp\htdocs\smart-expense-tracker`
2. Start **Apache** and **MySQL** in XAMPP
3. Open phpMyAdmin → Import `database/expense_tracker.sql`
4. Edit `config/db.php` if needed (default XAMPP: user `root`, password `""` or `root`)
5. Open in browser:
   - **User:** http://localhost/smart-expense-tracker/
   - **Admin:** http://localhost/smart-expense-tracker/admin/login.php

> On first admin login, credentials are auto-synced to `admin123` if the SQL hash does not match.

## Login Credentials

| Role | Email | Password |
|------|-------|----------|
| User (demo) | demo@tracker.com | demo123 |
| User | john@tracker.com | demo123 |
| Admin | admin@tracker.com | admin123 |

## Admin URLs

| Page | URL |
|------|-----|
| Login | `/admin/login.php` |
| Dashboard | `/admin/dashboard.php` |
| Users | `/admin/users.php` |
| Transactions | `/admin/transactions.php` |
| Analytics | `/admin/reports.php` |
| Activity Logs | `/admin/activity.php` |

## Security

- `password_hash()` / `password_verify()` for users and admins
- Prepared statements (SQL injection prevention)
- Session cookie: HttpOnly, SameSite=Lax
- Session timeout (3600 seconds)
- Role-based access: `requireLogin()` vs `requireAdminLogin()`
- Blocked users cannot login

## Viva Talking Points

1. Admin panel monitors all users with aggregate SQL across the platform.
2. Views simplify complex joins for reusable analytics.
3. Stored procedures encapsulate monthly reports and overspending detection.
4. Triggers enforce business rules at DB level (alerts, logs, notifications).
5. Chart.js visualizes trends for presentation and portfolio demos.

## License

Educational / academic mini project.
