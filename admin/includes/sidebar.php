<aside class="admin-sidebar">
    <div class="brand d-block text-white text-decoration-none">
        <i class="bi bi-shield-lock"></i> Admin Panel
    </div>
    <nav class="nav flex-column py-2">
        <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="nav-link <?= $currentPage === 'users.php' || $currentPage === 'user_view.php' ? 'active' : '' ?>" href="users.php">
            <i class="bi bi-people"></i> Users
        </a>
        <a class="nav-link <?= $currentPage === 'transactions.php' ? 'active' : '' ?>" href="transactions.php">
            <i class="bi bi-receipt"></i> Transactions
        </a>
        <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php">
            <i class="bi bi-graph-up"></i> Analytics
        </a>
        <a class="nav-link <?= $currentPage === 'activity.php' ? 'active' : '' ?>" href="activity.php">
            <i class="bi bi-journal-text"></i> Activity Logs
        </a>
        <hr class="border-secondary mx-3">
        <a class="nav-link" href="<?= $assetBase ?>dashboard.php" target="_blank">
            <i class="bi bi-box-arrow-up-right"></i> User Site
        </a>
        <a class="nav-link text-warning" href="logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>
</aside>
