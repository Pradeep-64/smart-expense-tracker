# Smart Expense Tracker with Budget Analysis and Spending Insights

A professional PHP + MySQL mini project for tracking income/expenses, category budgets, reports, charts, and smart spending suggestions.

## Tech Stack

- PHP 8+
- MySQL / MariaDB
- HTML5, CSS3, JavaScript
- Bootstrap 5
- Chart.js
- XAMPP (Apache + MySQL)

## Folder Structure

```text
smart-expense-tracker/
├── assets/
│   ├── css/style.css
│   └── js/app.js
├── config/
│   └── db.php
├── database/
│   └── expense_tracker.sql
├── docs/
│   └── ER_DIAGRAM.md
├── includes/
│   ├── auth.php
│   ├── header.php
│   └── footer.php
├── index.php
├── register.php
├── login.php
├── logout.php
├── dashboard.php
├── income.php
├── expenses.php
├── budgets.php
└── reports.php
```

## Features Implemented

- User authentication (register, login, logout, sessions, hashed password)
- Dashboard metrics (income, expenses, balance, savings, highest category, recent transactions)
- Income CRUD
- Expense CRUD
- Search/filter expenses by date, category, amount, payment method
- Budget management by category/month with overspending warning
- Reports (daily, weekly, monthly, yearly)
- Analytics charts:
  - Pie: category-wise expenses
  - Bar: monthly spending
  - Line: income vs expense comparison
- Smart suggestions using simple spending logic
- Relational DB schema with PK, FK, joins, group by, aggregate functions
- Stored procedure and trigger included

## Setup Instructions (XAMPP)

1. Copy `smart-expense-tracker` folder into `xampp/htdocs/`.
2. Start `Apache` and `MySQL` from XAMPP control panel.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`).
4. Create/import database by running `database/expense_tracker.sql`.
5. Verify DB credentials in `config/db.php`:
   - host: `localhost`
   - username: `root`
   - password: *(blank by default in XAMPP)*
   - database: `smart_expense_tracker`
6. Run project in browser:
   - `http://localhost/smart-expense-tracker/`

## Dummy Login

- Email: `demo@tracker.com`
- Password: `demo123`

## Viva/Resume Talking Points

- Implemented full-stack CRUD application with secure authentication.
- Used normalized relational schema and enforced data integrity with FKs.
- Added SQL stored procedure and trigger for business logic at DB level.
- Built report analytics and chart-based visual insights for decision support.
