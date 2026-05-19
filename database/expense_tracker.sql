-- Smart Expense Tracker — Full schema (User + Admin + DBMS analytics)
-- Import via phpMyAdmin or: mysql -u root -p < expense_tracker.sql

CREATE DATABASE IF NOT EXISTS smart_expense_tracker
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE smart_expense_tracker;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS monthly_totals;
DROP TABLE IF EXISTS budget_alerts;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS income;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admin;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ===================== CORE TABLES (3NF) =====================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_blocked TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_blocked (is_blocked)
) ENGINE=InnoDB;

CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_name VARCHAR(100) NOT NULL,
    admin_email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_email (admin_email)
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE,
    INDEX idx_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    source VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL CHECK (amount >= 0),
    date DATE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_income_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_income_user_date (user_id, date),
    INDEX idx_income_date (date)
) ENGINE=InnoDB;

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL CHECK (amount >= 0),
    payment_method ENUM('Cash', 'UPI', 'Debit Card', 'Credit Card', 'Net Banking') NOT NULL,
    date DATE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expense_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_expense_category FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_expense_user_date (user_id, date),
    INDEX idx_expense_category (category_id),
    INDEX idx_expense_payment (payment_method),
    INDEX idx_expense_amount (amount)
) ENGINE=InnoDB;

CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    month_year CHAR(7) NOT NULL,
    budget_amount DECIMAL(12,2) NOT NULL CHECK (budget_amount >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_budget_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_budget_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT uq_budget_user_category_month UNIQUE (user_id, category_id, month_year)
) ENGINE=InnoDB;

CREATE TABLE budget_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    month_year CHAR(7) NOT NULL,
    spent_amount DECIMAL(12,2) NOT NULL,
    budget_amount DECIMAL(12,2) NOT NULL,
    message VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alert_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(500) NOT NULL,
    status ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user_status (user_id, status)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_activity_user_date (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE monthly_totals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    month_year CHAR(7) NOT NULL,
    total_income DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_expenses DECIMAL(12,2) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_monthly_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_monthly_user_month (user_id, month_year),
    INDEX idx_monthly_month (month_year)
) ENGINE=InnoDB;

-- ===================== SEED DATA =====================

INSERT INTO categories (name) VALUES
('Food'), ('Travel'), ('Rent'), ('Shopping'), ('Bills'),
('Entertainment'), ('Education'), ('Medical'), ('Others');

-- Password: demo123
INSERT INTO users (name, email, password, is_blocked) VALUES
('Demo User', 'demo@tracker.com', '$2y$10$MP6A4.96ugyWtfETV0X5buK/0XemeO0XTzCwOKOKPOMlo2QxWF5q.', 0),
('John Kumar', 'john@tracker.com', '$2y$10$MP6A4.96ugyWtfETV0X5buK/0XemeO0XTzCwOKOKPOMlo2QxWF5q.', 0),
('Priya Sharma', 'priya@tracker.com', '$2y$10$MP6A4.96ugyWtfETV0X5buK/0XemeO0XTzCwOKOKPOMlo2QxWF5q.', 0),
('Rahul Mehta', 'rahul@tracker.com', '$2y$10$MP6A4.96ugyWtfETV0X5buK/0XemeO0XTzCwOKOKPOMlo2QxWF5q.', 0);

-- Password: admin123 (auto-corrected on first admin login if hash mismatch)
INSERT INTO admin (admin_name, admin_email, password) VALUES
('System Admin', 'pradeep@gmail.com', '0192023a7bbd73250516f069df18b500');

INSERT INTO income (user_id, source, amount, date, description) VALUES
(1, 'Salary', 50000, CURDATE(), 'Monthly salary'),
(1, 'Freelance', 8000, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Design project'),
(2, 'Salary', 45000, CURDATE(), 'IT job'),
(2, 'Bonus', 5000, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Q1 bonus'),
(3, 'Salary', 38000, CURDATE(), 'Teaching'),
(4, 'Business', 62000, CURDATE(), 'Shop revenue');

INSERT INTO expenses (user_id, category_id, amount, payment_method, date, description) VALUES
(1, 1, 3500, 'UPI', CURDATE(), 'Groceries'),
(1, 2, 1500, 'Cash', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Cab'),
(1, 4, 2800, 'Credit Card', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Clothing'),
(1, 5, 2200, 'Net Banking', DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Bills'),
(2, 1, 4200, 'UPI', CURDATE(), 'Dining'),
(2, 4, 8500, 'Credit Card', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Electronics'),
(2, 2, 3200, 'UPI', DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Flight'),
(3, 1, 2800, 'Cash', CURDATE(), 'Food'),
(3, 3, 12000, 'Net Banking', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Rent'),
(4, 4, 15000, 'Credit Card', CURDATE(), 'Inventory'),
(4, 5, 4500, 'UPI', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Utilities');

INSERT INTO budgets (user_id, category_id, month_year, budget_amount) VALUES
(1, 1, DATE_FORMAT(CURDATE(), '%Y-%m'), 5000),
(1, 4, DATE_FORMAT(CURDATE(), '%Y-%m'), 2500),
(2, 4, DATE_FORMAT(CURDATE(), '%Y-%m'), 6000),
(4, 4, DATE_FORMAT(CURDATE(), '%Y-%m'), 10000);

-- ===================== SQL VIEWS =====================

CREATE OR REPLACE VIEW monthly_expense_summary AS
SELECT
    DATE_FORMAT(e.date, '%Y-%m') AS month_year,
    u.id AS user_id,
    u.name AS user_name,
    COUNT(e.id) AS transaction_count,
    COALESCE(SUM(e.amount), 0) AS total_expenses
FROM users u
LEFT JOIN expenses e ON e.user_id = u.id
GROUP BY DATE_FORMAT(e.date, '%Y-%m'), u.id, u.name;

CREATE OR REPLACE VIEW category_wise_expenses AS
SELECT
    c.id AS category_id,
    c.name AS category_name,
    COUNT(e.id) AS expense_count,
    COALESCE(SUM(e.amount), 0) AS total_amount,
    COALESCE(AVG(e.amount), 0) AS avg_amount
FROM categories c
LEFT JOIN expenses e ON e.category_id = c.id
GROUP BY c.id, c.name;

CREATE OR REPLACE VIEW user_financial_summary AS
SELECT
    u.id AS user_id,
    u.name,
    u.email,
    u.is_blocked,
    COALESCE((SELECT SUM(amount) FROM income i WHERE i.user_id = u.id), 0) AS total_income,
    COALESCE((SELECT SUM(amount) FROM expenses ex WHERE ex.user_id = u.id), 0) AS total_expenses,
    COALESCE((SELECT SUM(amount) FROM income i WHERE i.user_id = u.id), 0)
        - COALESCE((SELECT SUM(amount) FROM expenses ex WHERE ex.user_id = u.id), 0) AS total_savings,
    (SELECT COUNT(*) FROM expenses ex WHERE ex.user_id = u.id)
        + (SELECT COUNT(*) FROM income i WHERE i.user_id = u.id) AS total_transactions
FROM users u;

CREATE OR REPLACE VIEW top_spending_users AS
SELECT
    u.id AS user_id,
    u.name,
    u.email,
    COALESCE(SUM(e.amount), 0) AS total_spent
FROM users u
LEFT JOIN expenses e ON e.user_id = u.id
GROUP BY u.id, u.name, u.email
ORDER BY total_spent DESC;

-- ===================== STORED PROCEDURES =====================

DELIMITER //

DROP PROCEDURE IF EXISTS GetMonthlySummary //
CREATE PROCEDURE GetMonthlySummary(IN p_user_id INT, IN p_month CHAR(7))
BEGIN
    SELECT
        p_month AS month_year,
        COALESCE((SELECT SUM(amount) FROM income WHERE user_id = p_user_id AND DATE_FORMAT(date, '%Y-%m') = p_month), 0) AS total_income,
        COALESCE((SELECT SUM(amount) FROM expenses WHERE user_id = p_user_id AND DATE_FORMAT(date, '%Y-%m') = p_month), 0) AS total_expense;
END //

DROP PROCEDURE IF EXISTS sp_generate_monthly_report //
CREATE PROCEDURE sp_generate_monthly_report(IN p_month CHAR(7))
BEGIN
    SELECT
        u.id AS user_id,
        u.name,
        COALESCE(SUM(i.amount), 0) AS month_income,
        COALESCE(SUM(e.amount), 0) AS month_expense,
        COALESCE(SUM(i.amount), 0) - COALESCE(SUM(e.amount), 0) AS month_savings
    FROM users u
    LEFT JOIN income i ON i.user_id = u.id AND DATE_FORMAT(i.date, '%Y-%m') = p_month
    LEFT JOIN expenses e ON e.user_id = u.id AND DATE_FORMAT(e.date, '%Y-%m') = p_month
    GROUP BY u.id, u.name
    ORDER BY month_expense DESC;
END //

DROP PROCEDURE IF EXISTS sp_calculate_total_savings //
CREATE PROCEDURE sp_calculate_total_savings()
BEGIN
    SELECT
        COALESCE((SELECT SUM(amount) FROM income), 0) AS platform_total_income,
        COALESCE((SELECT SUM(amount) FROM expenses), 0) AS platform_total_expenses,
        COALESCE((SELECT SUM(amount) FROM income), 0) - COALESCE((SELECT SUM(amount) FROM expenses), 0) AS platform_total_savings;
END //

DROP PROCEDURE IF EXISTS sp_category_analysis //
CREATE PROCEDURE sp_category_analysis(IN p_month CHAR(7))
BEGIN
    SELECT
        c.name AS category_name,
        COUNT(e.id) AS tx_count,
        COALESCE(SUM(e.amount), 0) AS total_spent,
        COALESCE(AVG(e.amount), 0) AS avg_spent
    FROM categories c
    INNER JOIN expenses e ON e.category_id = c.id
    WHERE DATE_FORMAT(e.date, '%Y-%m') = p_month
    GROUP BY c.id, c.name
    HAVING total_spent > 0
    ORDER BY total_spent DESC;
END //

DROP PROCEDURE IF EXISTS sp_detect_overspending //
CREATE PROCEDURE sp_detect_overspending(IN p_user_id INT, IN p_month CHAR(7))
BEGIN
    SELECT
        u.name AS user_name,
        c.name AS category_name,
        b.budget_amount,
        COALESCE(SUM(e.amount), 0) AS spent_amount,
        COALESCE(SUM(e.amount), 0) - b.budget_amount AS exceeded_by
    FROM budgets b
    INNER JOIN users u ON u.id = b.user_id
    INNER JOIN categories c ON c.id = b.category_id
    LEFT JOIN expenses e ON e.user_id = b.user_id
        AND e.category_id = b.category_id
        AND DATE_FORMAT(e.date, '%Y-%m') = p_month
    WHERE b.user_id = p_user_id AND b.month_year = p_month
    GROUP BY u.name, c.name, b.budget_amount
    HAVING spent_amount > b.budget_amount;
END //

DELIMITER ;

-- ===================== TRIGGERS =====================

DELIMITER //

DROP TRIGGER IF EXISTS trg_budget_alert_after_expense //
CREATE TRIGGER trg_budget_alert_after_expense
AFTER INSERT ON expenses
FOR EACH ROW
BEGIN
    DECLARE v_budget DECIMAL(12,2) DEFAULT 0;
    DECLARE v_spent DECIMAL(12,2) DEFAULT 0;
    DECLARE v_month CHAR(7);
    DECLARE v_cat_name VARCHAR(60);
    DECLARE v_user_name VARCHAR(100);

    SET v_month = DATE_FORMAT(NEW.date, '%Y-%m');

    SELECT budget_amount INTO v_budget
    FROM budgets
    WHERE user_id = NEW.user_id AND category_id = NEW.category_id AND month_year = v_month
    LIMIT 1;

    IF v_budget IS NOT NULL THEN
        SELECT COALESCE(SUM(amount), 0) INTO v_spent
        FROM expenses
        WHERE user_id = NEW.user_id AND category_id = NEW.category_id
          AND DATE_FORMAT(date, '%Y-%m') = v_month;

        IF v_spent > v_budget THEN
            SELECT name INTO v_cat_name FROM categories WHERE id = NEW.category_id;
            SELECT name INTO v_user_name FROM users WHERE id = NEW.user_id;

            INSERT INTO budget_alerts(user_id, category_id, month_year, spent_amount, budget_amount, message)
            VALUES (NEW.user_id, NEW.category_id, v_month, v_spent, v_budget,
                CONCAT('Budget exceeded by Rs ', FORMAT(v_spent - v_budget, 2), ' in ', v_cat_name, '.'));

            INSERT INTO notifications(user_id, message, status)
            VALUES (NEW.user_id,
                CONCAT('Budget alert: You exceeded ', v_cat_name, ' budget by Rs ', FORMAT(v_spent - v_budget, 2), '.'),
                'unread');
        END IF;
    END IF;
END //

DROP TRIGGER IF EXISTS trg_expense_activity_log //
CREATE TRIGGER trg_expense_activity_log
AFTER INSERT ON expenses
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs(user_id, activity)
    VALUES (NEW.user_id, CONCAT('Added expense of Rs ', NEW.amount, ' on ', NEW.date));
END //

DROP TRIGGER IF EXISTS trg_income_activity_log //
CREATE TRIGGER trg_income_activity_log
AFTER INSERT ON income
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs(user_id, activity)
    VALUES (NEW.user_id, CONCAT('Added income of Rs ', NEW.amount, ' from ', NEW.source));
END //

DROP TRIGGER IF EXISTS trg_update_monthly_totals_expense //
CREATE TRIGGER trg_update_monthly_totals_expense
AFTER INSERT ON expenses
FOR EACH ROW
BEGIN
    DECLARE v_month CHAR(7);
    SET v_month = DATE_FORMAT(NEW.date, '%Y-%m');

    INSERT INTO monthly_totals (user_id, month_year, total_expenses, total_income)
    VALUES (NEW.user_id, v_month, NEW.amount, 0)
    ON DUPLICATE KEY UPDATE
        total_expenses = total_expenses + NEW.amount,
        updated_at = CURRENT_TIMESTAMP;
END //

DROP TRIGGER IF EXISTS trg_update_monthly_totals_income //
CREATE TRIGGER trg_update_monthly_totals_income
AFTER INSERT ON income
FOR EACH ROW
BEGIN
    DECLARE v_month CHAR(7);
    SET v_month = DATE_FORMAT(NEW.date, '%Y-%m');

    INSERT INTO monthly_totals (user_id, month_year, total_income, total_expenses)
    VALUES (NEW.user_id, v_month, NEW.amount, 0)
    ON DUPLICATE KEY UPDATE
        total_income = total_income + NEW.amount,
        updated_at = CURRENT_TIMESTAMP;
END //

DELIMITER ;

-- Backfill monthly_totals from existing data
INSERT INTO monthly_totals (user_id, month_year, total_income, total_expenses)
SELECT user_id, DATE_FORMAT(date, '%Y-%m'), SUM(amount), 0 FROM income GROUP BY user_id, DATE_FORMAT(date, '%Y-%m')
ON DUPLICATE KEY UPDATE total_income = VALUES(total_income);

INSERT INTO monthly_totals (user_id, month_year, total_income, total_expenses)
SELECT user_id, DATE_FORMAT(date, '%Y-%m'), 0, SUM(amount) FROM expenses GROUP BY user_id, DATE_FORMAT(date, '%Y-%m')
ON DUPLICATE KEY UPDATE total_expenses = monthly_totals.total_expenses + VALUES(total_expenses);
