<?php
// admin/includes/admin_sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <p class="admin-sidebar__title">DJ Store Admin</p>
    <nav class="admin-nav">
        <a href="<?= SITE_URL ?>/admin/index.php"
           class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
            📊 Табло
        </a>
        <a href="<?= SITE_URL ?>/admin/products.php"
           class="<?= in_array($currentPage, ['products.php','products_add.php','products_edit.php']) ? 'active' : '' ?>">
            🎛️ Продукти
        </a>
        <a href="<?= SITE_URL ?>/admin/categories.php"
           class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>">
            📂 Категории
        </a>
        <a href="<?= SITE_URL ?>/admin/orders.php"
           class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">
            📦 Поръчки
        </a>
        <a href="<?= SITE_URL ?>/admin/promo_codes.php"
           class="<?= $currentPage === 'promo_codes.php' ? 'active' : '' ?>">
            🏷️ Промо кодове
        </a>
        <div style="margin-top:auto;padding:24px 0 0;">
            <a href="<?= SITE_URL ?>" style="color:var(--grey);">
                ← Към магазина
            </a>
        </div>
    </nav>
</aside>