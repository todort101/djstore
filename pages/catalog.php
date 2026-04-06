<?php
// pages/catalog.php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/cart.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Събери филтри
$filters = [];
$activeCat = null;

if (!empty($_GET['category'])) {
    $activeCat = getCategoryBySlug(trim($_GET['category']));
    if ($activeCat) $filters['category_id'] = $activeCat['id'];
}
if (!empty($_GET['search']))    $filters['search']    = trim($_GET['search']);
if (!empty($_GET['min_price'])) $filters['min_price'] = (float)$_GET['min_price'];
if (!empty($_GET['max_price'])) $filters['max_price'] = (float)$_GET['max_price'];
if (!empty($_GET['sort']))      $filters['sort']      = $_GET['sort'];

$products   = getProducts($filters);
$categories = getAllCategories();
$cartCount  = getCartCount();

$pageTitle = $activeCat ? e($activeCat['name']) : 'Всички продукти';
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main>
    <div class="container">
        <div class="catalog-layout">

            <!-- ── SIDEBAR ───────────────────────────── -->
            <aside class="sidebar">
                <p class="sidebar__title">Филтри</p>

                <!-- Търсене -->
                <div class="sidebar__section">
                    <p class="sidebar__section-label">Търсене</p>
                    <form method="GET" id="filterForm">
                        <?php if (!empty($_GET['category'])): ?>
                        <input type="hidden" name="category" value="<?= e($_GET['category']) ?>">
                        <?php endif; ?>
                        <div class="form-group" style="margin-bottom:0">
                            <input type="text" name="search"
                                   value="<?= e($_GET['search'] ?? '') ?>"
                                   placeholder="Търси продукт..."
                                   style="background:var(--dark-3)">
                        </div>
                    </form>
                </div>

                <!-- Категории -->
                <div class="sidebar__section">
                    <p class="sidebar__section-label">Категория</p>
                    <a href="<?= SITE_URL ?>/pages/catalog.php"
                       class="filter-cat <?= !$activeCat ? 'active' : '' ?>">
                        Всички продукти
                    </a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="<?= SITE_URL ?>/pages/catalog.php?category=<?= e($cat['slug']) ?><?= !empty($_GET['search']) ? '&search=' . e($_GET['search']) : '' ?>"
                       class="filter-cat <?= ($activeCat && $activeCat['id'] == $cat['id']) ? 'active' : '' ?>">
                        <?= e($cat['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- Цена -->
                <div class="sidebar__section">
                    <p class="sidebar__section-label">Цена (лв.)</p>
                    <form method="GET" style="display:flex;gap:8px;flex-direction:column">
                        <?php if (!empty($_GET['category'])): ?>
                        <input type="hidden" name="category" value="<?= e($_GET['category']) ?>">
                        <?php endif; ?>
                        <?php if (!empty($_GET['search'])): ?>
                        <input type="hidden" name="search" value="<?= e($_GET['search']) ?>">
                        <?php endif; ?>
                        <div style="display:flex;gap:8px">
                            <input type="number" name="min_price"
                                   value="<?= e($_GET['min_price'] ?? '') ?>"
                                   placeholder="От" min="0" step="1"
                                   style="width:50%;background:var(--dark-3);border:1px solid var(--dark-4);border-radius:4px;padding:8px;color:var(--white)">
                            <input type="number" name="max_price"
                                   value="<?= e($_GET['max_price'] ?? '') ?>"
                                   placeholder="До" min="0" step="1"
                                   style="width:50%;background:var(--dark-3);border:1px solid var(--dark-4);border-radius:4px;padding:8px;color:var(--white)">
                        </div>
                        <button type="submit" class="btn btn--ghost btn--sm">
                            Приложи
                        </button>
                    </form>
                </div>

                <!-- Изчисти -->
                <?php if (!empty($_GET)): ?>
                <a href="<?= SITE_URL ?>/pages/catalog.php"
                   class="btn btn--ghost btn--sm btn--full">
                    ✕ Изчисти филтрите
                </a>
                <?php endif; ?>
            </aside>

            <!-- ── PRODUCTS ──────────────────────────── -->
            <div>
                <div class="catalog-toolbar">
                    <p class="catalog-count">
                        <?php if ($activeCat): ?>
                            <strong><?= e($activeCat['name']) ?></strong> —
                        <?php endif; ?>
                        <?= count($products) ?> продукта
                        <?php if (!empty($filters['search'])): ?>
                            за „<?= e($filters['search']) ?>"
                        <?php endif; ?>
                    </p>
                    <div class="catalog-sort">
                        <form method="GET" id="sortForm">
                            <?php foreach ($_GET as $k => $v): ?>
                                <?php if ($k !== 'sort'): ?>
                                <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <select name="sort" onchange="document.getElementById('sortForm').submit()">
                                <option value=""           <?= empty($_GET['sort']) ? 'selected' : '' ?>>Най-нови</option>
                                <option value="price_asc"  <?= ($_GET['sort'] ?? '') === 'price_asc'  ? 'selected' : '' ?>>Цена: ↑ Нарастваща</option>
                                <option value="price_desc" <?= ($_GET['sort'] ?? '') === 'price_desc' ? 'selected' : '' ?>>Цена: ↓ Намаляваща</option>
                                <option value="name_asc"   <?= ($_GET['sort'] ?? '') === 'name_asc'   ? 'selected' : '' ?>>Наименование А-Я</option>
                            </select>
                        </form>
                    </div>
                </div>

                <?php showFlash(); ?>

                <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="empty-state__icon">🔍</div>
                    <h2 class="empty-state__title">Няма намерени продукти</h2>
                    <p>Опитай с различни филтри или ключови думи.</p>
                    <a href="<?= SITE_URL ?>/pages/catalog.php" class="btn btn--primary">
                        Виж всички
                    </a>
                </div>
                <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $p): ?>
                    <div class="product-card">
                        <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= e($p['slug']) ?>"
                           class="product-card__img-wrap">
                            <img src="<?= productImage($p['image']) ?>"
                                 alt="<?= e($p['name']) ?>" loading="lazy">
                            <?php if ($p['stock'] == 0): ?>
                            <span class="badge badge--out">Изчерпан</span>
                            <?php endif; ?>
                        </a>
                        <div class="product-card__body">
                            <span class="product-card__brand"><?= e($p['brand'] ?? '') ?></span>
                            <h3 class="product-card__name">
                                <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= e($p['slug']) ?>">
                                    <?= e($p['name']) ?>
                                </a>
                            </h3>
                            <div class="product-card__footer">
                                <span class="product-card__price"><?= formatPrice($p['price']) ?></span>
                                <?php if ($p['stock'] > 0): ?>
                                <form method="POST" action="<?= SITE_URL ?>/pages/cart.php">
                                    <input type="hidden" name="action"     value="add">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="redirect"   value="catalog">
                                    <button type="submit" class="btn-cart" title="Добави в кошница">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                                            <line x1="3" y1="6" x2="21" y2="6"/>
                                            <path d="M16 10a4 4 0 01-8 0"/>
                                        </svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /catalog-layout -->
    </div>
</main>

<?php include '../includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>