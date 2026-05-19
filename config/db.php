<?php
declare(strict_types=1);

$host = "localhost";
$username = "root";
$password = ""; // XAMPP default is empty; use "root" if your MySQL requires it
$database = "smart_expense_tracker";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
