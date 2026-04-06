<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($cartCount)) {
    require_once __DIR__ . '/../includes/cart.php';
    $cartCount = getCartCount();
}
$categories = getAllCategories();
?>
<header class="site-header">
    <div class="container header__inner">

        <!-- Logo -->
        <a href="<?= SITE_URL ?>" class="logo">
            <span class="logo__icon">⬡</span>
            <span class="logo__text">DJ<strong>STORE</strong></span>
        </a>

        <!-- Main nav -->
        <nav class="main-nav">

            <!-- Каталог с dropdown -->
            <div class="nav-dropdown">
                <a href="<?= SITE_URL ?>/pages/catalog.php" class="nav-dropdown__trigger">
                    Каталог ▾
                </a>
                <div class="nav-dropdown__menu">
                    <a href="<?= SITE_URL ?>/pages/catalog.php"
                       class="nav-dropdown__item nav-dropdown__item--all">
                        🎛️ Всички продукти
                    </a>
                    <div class="nav-dropdown__divider"></div>
                    <?php foreach ($categories as $cat): ?>
                    <a href="<?= SITE_URL ?>/pages/catalog.php?category=<?= e($cat['slug']) ?>"
                       class="nav-dropdown__item">
                        <?= e($cat['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </nav>

        <!-- Търсачка -->
        <form method="GET"
              action="<?= SITE_URL ?>/pages/catalog.php"
              class="header-search">
            <input type="text"
                   name="search"
                   placeholder="Търси продукт..."
                   value="<?= e($_GET['search'] ?? '') ?>"
                   autocomplete="off">
            <button type="submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" width="18" height="18">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </button>
        </form>

        <!-- Actions -->
        <div class="header__actions">

            <!-- Кошница -->
            <a href="<?= SITE_URL ?>/pages/cart.php" class="header-cart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" width="22" height="22">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 01-8 0"/>
                </svg>
                <?php if ($cartCount > 0): ?>
                <span class="header-cart__badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>

            <!-- User menu -->
            <?php if (isLoggedIn()): ?>
            <div class="header__user-menu">
                <button class="header__user-btn">
                    <?= e($_SESSION['username']) ?> ▾
                </button>
                <div class="header__dropdown">
                    <?php if (isAdmin()): ?>
                    <a href="<?= SITE_URL ?>/admin/">⚙ Админ панел</a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/pages/orders.php">📦 Моите поръчки</a>
                    <a href="<?= SITE_URL ?>/pages/profile.php">👤 Профил</a>
                    <a href="<?= SITE_URL ?>/pages/logout.php"
                       class="dropdown-danger">⏻ Изход</a>
                </div>
            </div>
            <?php else: ?>
            <a href="<?= SITE_URL ?>/pages/login.php"
               class="btn btn--sm btn--ghost">Вход</a>
            <a href="<?= SITE_URL ?>/pages/register.php"
               class="btn btn--sm btn--primary">Регистрация</a>
            <?php endif; ?>
        </div>

        <button class="mobile-toggle" id="mobileToggle">☰</button>
    </div>

    <!-- Mobile nav -->
    <nav class="mobile-nav" id="mobileNav">
        <a href="<?= SITE_URL ?>/pages/catalog.php">Каталог</a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?= SITE_URL ?>/pages/catalog.php?category=<?= e($cat['slug']) ?>">
            <?= e($cat['name']) ?>
        </a>
        <?php endforeach; ?>
        <form method="GET"
              action="<?= SITE_URL ?>/pages/catalog.php"
              style="padding:12px clamp(16px,4vw,48px);">
            <div style="display:flex;gap:8px;">
                <input type="text"
                       name="search"
                       placeholder="Търси продукт..."
                       style="flex:1;background:var(--dark-3);border:1px solid var(--dark-4);
                              border-radius:4px;padding:8px 12px;color:var(--white);">
                <button type="submit" class="btn btn--primary btn--sm">→</button>
            </div>
        </form>
        <?php if (isLoggedIn()): ?>
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/">⚙ Админ панел</a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/pages/orders.php">📦 Моите поръчки</a>
            <a href="<?= SITE_URL ?>/pages/logout.php">⏻ Изход</a>
        <?php else: ?>
            <a href="<?= SITE_URL ?>/pages/login.php">Вход</a>
            <a href="<?= SITE_URL ?>/pages/register.php">Регистрация</a>
        <?php endif; ?>
    </nav>
</header>