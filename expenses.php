<?php
declare(strict_types=1);
require_once "config/db.php";
require_once "includes/auth.php";
requireLogin();
$userId = (int) $_SESSION["user_id"];

if (isset($_POST["add_expense"])) {
    $categoryId = (int) $_POST["category_id"];
    $amount = (float) $_POST["amount"];
    $paymentMethod = $_POST["payment_method"];
    $date = $_POST["date"];
    $description = trim($_POST["description"]);
    $stmt = $conn->prepare("INSERT INTO expenses(user_id, category_id, amount, payment_method, date, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidsss", $userId, $categoryId, $amount, $paymentMethod, $date, $description);
    $stmt->execute();
}

if (isset($_POST["update_expense"])) {
    $id = (int) $_POST["id"];
    $categoryId = (int) $_POST["category_id"];
    $amount = (float) $_POST["amount"];
    $paymentMethod = $_POST["payment_method"];
    $date = $_POST["date"];
    $description = trim($_POST["description"]);
    $stmt = $conn->prepare("UPDATE expenses SET category_id=?, amount=?, payment_method=?, date=?, description=? WHERE id=? AND user_id=?");
    $stmt->bind_param("idsssii", $categoryId, $amount, $paymentMethod, $date, $description, $id, $userId);
    $stmt->execute();
}

if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    $conn->query("DELETE FROM expenses WHERE id=$id AND user_id=$userId");
    header("Location: expenses.php");
    exit;
}

$editData = null;
if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];
    $editData = $conn->query("SELECT * FROM expenses WHERE id=$id AND user_id=$userId")->fetch_assoc();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$filterDate = $_GET["f_date"] ?? "";
$filterCategory = $_GET["f_category"] ?? "";
$filterAmount = $_GET["f_amount"] ?? "";
$filterPay = $_GET["f_payment"] ?? "";

$query = "SELECT e.*, c.name AS category_name FROM expenses e JOIN categories c ON c.id=e.category_id WHERE e.user_id=$userId";
if ($filterDate !== "") {
    $query .= " AND e.date='" . $conn->real_escape_string($filterDate) . "'";
}
if ($filterCategory !== "") {
    $query .= " AND c.name='" . $conn->real_escape_string($filterCategory) . "'";
}
if ($filterAmount !== "") {
    $query .= " AND e.amount=" . (float) $filterAmount;
}
if ($filterPay !== "") {
    $query .= " AND e.payment_method='" . $conn->real_escape_string($filterPay) . "'";
}
$query .= " ORDER BY e.date DESC";
$rows = $conn->query($query);

include "includes/header.php";
?>
<h3 class="mb-3">Expense Management</h3>
<div class="card mb-4">
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="id" value="<?= $editData["id"] ?? "" ?>">
            <div class="col-md-2">
                <select name="category_id" class="form-select" required>
                    <option value="">Category</option>
                    <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                    <option value="<?= $c["id"] ?>" <?= isset($editData["category_id"]) && (int) $editData["category_id"] === (int) $c["id"] ? "selected" : "" ?>><?= htmlspecialchars($c["name"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="number" step="0.01" class="form-control" name="amount" placeholder="Amount" value="<?= $editData["amount"] ?? "" ?>" required></div>
            <div class="col-md-2">
                <select name="payment_method" class="form-select" required>
                    <?php foreach (["Cash","UPI","Debit Card","Credit Card","Net Banking"] as $m): ?>
                    <option value="<?= $m ?>" <?= isset($editData["payment_method"]) && $editData["payment_method"] === $m ? "selected" : "" ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="date" class="form-control" name="date" value="<?= $editData["date"] ?? date("Y-m-d") ?>" required></div>
            <div class="col-md-3"><input class="form-control" name="description" placeholder="Description" value="<?= htmlspecialchars($editData["description"] ?? "") ?>"></div>
            <div class="col-md-1 d-grid"><?= $editData ? '<button class="btn btn-warning" name="update_expense">Update</button>' : '<button class="btn btn-danger" name="add_expense">Add</button>' ?></div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5>Search & Filter</h5>
        <form class="row g-2">
            <div class="col-md-3"><input type="date" class="form-control" name="f_date" value="<?= htmlspecialchars($filterDate) ?>"></div>
            <div class="col-md-3">
                <select name="f_category" class="form-select">
                    <option value="">All Categories</option>
                    <?php $categories->data_seek(0); while ($c = $categories->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($c["name"]) ?>" <?= $filterCategory === $c["name"] ? "selected" : "" ?>><?= htmlspecialchars($c["name"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="number" step="0.01" class="form-control" name="f_amount" placeholder="Amount" value="<?= htmlspecialchars($filterAmount) ?>"></div>
            <div class="col-md-2">
                <select name="f_payment" class="form-select">
                    <option value="">All Methods</option>
                    <?php foreach (["Cash","UPI","Debit Card","Credit Card","Net Banking"] as $m): ?>
                    <option value="<?= $m ?>" <?= $filterPay === $m ? "selected" : "" ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Apply</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5>Expense History</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Category</th><th>Amount</th><th>Payment</th><th>Date</th><th>Description</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($row = $rows->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row["category_name"]) ?></td>
                        <td>Rs <?= number_format((float) $row["amount"], 2) ?></td>
                        <td><?= htmlspecialchars($row["payment_method"]) ?></td>
                        <td><?= htmlspecialchars($row["date"]) ?></td>
                        <td><?= htmlspecialchars((string) $row["description"]) ?></td>
                        <td>
                            <a href="expenses.php?edit=<?= $row["id"] ?>" class="btn btn-sm btn-outline-warning">Edit</a>
                            <a href="expenses.php?delete=<?= $row["id"] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this expense?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include "includes/footer.php"; ?>
