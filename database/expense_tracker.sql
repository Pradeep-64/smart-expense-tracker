CREATE DATABASE IF NOT EXISTS smart_expense_tracker;
USE smart_expense_tracker;

DROP TABLE IF EXISTS budget_alerts;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS income;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
);

CREATE TABLE income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    source VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL CHECK (amount >= 0),
    date DATE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_income_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL CHECK (amount >= 0),
    payment_method ENUM('Cash', 'UPI', 'Debit Card', 'Credit Card', 'Net Banking') NOT NULL,
    date DATE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expense_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_expense_category FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    month_year CHAR(7) NOT NULL,
    budget_amount DECIMAL(10,2) NOT NULL CHECK (budget_amount >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_budget_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_budget_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT uq_budget_user_category_month UNIQUE (user_id, category_id, month_year)
);

CREATE TABLE budget_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    month_year CHAR(7) NOT NULL,
    spent_amount DECIMAL(10,2) NOT NULL,
    budget_amount DECIMAL(10,2) NOT NULL,
    message VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_alert_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_alert_category FOREIGN KEY (category_id) REFERENCES categories(id)
);

INSERT INTO categories (name) VALUES
('Food'),
('Travel'),
('Rent'),
('Shopping'),
('Bills'),
('Entertainment'),
('Education'),
('Medical'),
('Others');

INSERT INTO users (name, email, password) VALUES
('Demo User', 'demo@tracker.com', '$2y$10$Wg8w1W4QgAq8FynM6H6S9.8sR4JBR6L23t3l0uQSaP5epM9fR94li');
-- demo password: demo123

INSERT INTO income (user_id, source, amount, date, description) VALUES
(1, 'Salary', 50000, CURDATE(), 'Monthly salary'),
(1, 'Freelance', 8000, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Design project');

INSERT INTO expenses (user_id, category_id, amount, payment_method, date, description) VALUES
(1, 1, 3500, 'UPI', CURDATE(), 'Groceries and dining'),
(1, 2, 1500, 'Cash', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Cab and fuel'),
(1, 4, 2800, 'Credit Card', DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Clothing purchase'),
(1, 5, 2200, 'Net Banking', DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Electricity and internet');

INSERT INTO budgets (user_id, category_id, month_year, budget_amount) VALUES
(1, 1, DATE_FORMAT(CURDATE(), '%Y-%m'), 5000),
(1, 4, DATE_FORMAT(CURDATE(), '%Y-%m'), 2500),
(1, 5, DATE_FORMAT(CURDATE(), '%Y-%m'), 3000);

DELIMITER //
CREATE PROCEDURE GetMonthlySummary(IN p_user_id INT, IN p_month CHAR(7))
BEGIN
    SELECT
        p_month AS month_year,
        COALESCE((SELECT SUM(amount) FROM income WHERE user_id = p_user_id AND DATE_FORMAT(date, '%Y-%m') = p_month), 0) AS total_income,
        COALESCE((SELECT SUM(amount) FROM expenses WHERE user_id = p_user_id AND DATE_FORMAT(date, '%Y-%m') = p_month), 0) AS total_expense;
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER trg_budget_alert_after_expense
AFTER INSERT ON expenses
FOR EACH ROW
BEGIN
    DECLARE v_budget DECIMAL(10,2) DEFAULT 0;
    DECLARE v_spent DECIMAL(10,2) DEFAULT 0;
    DECLARE v_month CHAR(7);
    SET v_month = DATE_FORMAT(NEW.date, '%Y-%m');

    SELECT budget_amount INTO v_budget
    FROM budgets
    WHERE user_id = NEW.user_id
      AND category_id = NEW.category_id
      AND month_year = v_month
    LIMIT 1;

    IF v_budget IS NOT NULL THEN
        SELECT COALESCE(SUM(amount), 0) INTO v_spent
        FROM expenses
        WHERE user_id = NEW.user_id
          AND category_id = NEW.category_id
          AND DATE_FORMAT(date, '%Y-%m') = v_month;

        IF v_spent > v_budget THEN
            INSERT INTO budget_alerts(user_id, category_id, month_year, spent_amount, budget_amount, message)
            VALUES (
                NEW.user_id,
                NEW.category_id,
                v_month,
                v_spent,
                v_budget,
                CONCAT('Budget exceeded by ', FORMAT(v_spent - v_budget, 2), ' in category.')
            );
        END IF;
    END IF;
END //
DELIMITER ;
