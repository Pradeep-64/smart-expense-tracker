<div class="admin-topbar">
    <div>
        <h2 class="mb-0"><?= e($pageTitle ?? 'Admin') ?></h2>
        <?php if (!empty($pageSubtitle)): ?>
            <p class="text-muted mb-0"><?= e($pageSubtitle) ?></p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 no-print flex-wrap">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <button type="button" class="btn btn-outline-dark btn-sm" id="themeToggle"><i class="bi bi-moon-stars"></i></button>
        <span class="align-self-center small"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></span>
    </div>
</div>
