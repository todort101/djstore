<?php
// pages/product.php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/cart.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$slug    = trim($_GET['slug'] ?? '');
$product = $slug ? getProductBySlug($slug) : null;

if (!$product) {
    http_response_code(404);
    setFlash('error', 'Продуктът не е намерен.');
    header('Location: ' . SITE_URL . '/pages/catalog.php');
    exit;
}

// Свързани продукти (от същата категория)
$related = array_filter(
    getProducts(['category_id' => $product['category_id']]),
    fn($p) => $p['id'] !== $product['id']
);
$related   = array_slice($related, 0, 4);
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($product['name']) ?> — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="product-page">
    <div class="container">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="<?= SITE_URL ?>">Начало</a>
            <span class="breadcrumb-sep">›</span>
            <a href="<?= SITE_URL ?>/pages/catalog.php">Каталог</a>
            <span class="breadcrumb-sep">›</span>
            <a href="<?= SITE_URL ?>/pages/catalog.php?category=<?= e(createSlug($product['category_name'])) ?>">
                <?= e($product['category_name']) ?>
            </a>
            <span class="breadcrumb-sep">›</span>
            <span><?= e($product['name']) ?></span>
        </nav>

        <?php showFlash(); ?>

        <div class="product-layout">

            <!-- Снимка -->
            <div class="product-img-wrap">
                <img src="<?= productImage($product['image']) ?>"
                     alt="<?= e($product['name']) ?>">
            </div>

            <!-- Детайли -->
            <div class="product-details">
                <p class="product-brand"><?= e($product['brand'] ?? '') ?></p>
                <h1 class="product-name"><?= e($product['name']) ?></h1>
                <p class="product-price"><?= formatPrice($product['price']) ?></p>

                <div class="product-meta">
                    <div class="product-meta-row">
                        <span>Категория</span>
                        <span><?= e($product['category_name']) ?></span>
                    </div>
                    <?php if ($product['brand']): ?>
                    <div class="product-meta-row">
                        <span>Марка</span>
                        <span><?= e($product['brand']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="product-meta-row">
                        <span>Наличност</span>
                        <?php if ($product['stock'] > 0): ?>
                        <span class="product-stock-ok">✓ В наличност (<?= $product['stock'] ?> бр.)</span>
                        <?php else: ?>
                        <span class="product-stock-out">✗ Изчерпан</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($product['stock'] > 0): ?>
                <form method="POST" action="<?= SITE_URL ?>/pages/cart.php"
                    class="product-add-form">
                    <input type="hidden" name="action"     value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="number" name="quantity"   value="1"
                        min="1" max="<?= $product['stock'] ?>"
                        class="qty-input">
                    <button type="submit" class="btn btn--primary">
                        🛒 Добави в кошница
                    </button>
                </form>
                <?php else: ?>
                <p class="alert alert-warning">Продуктът в момента е изчерпан.</p>
                <?php endif; ?>

                <?php if ($product['description']): ?>
                <p class="product-desc" style="margin-top:24px;">
                    <?= nl2br(e($product['description'])) ?>
                </p>
                <?php endif; ?>
            </div>

        </div><!-- /product-layout -->

        <!-- Свързани продукти -->
        <?php if (!empty($related)): ?>
        <section class="section" style="padding-bottom:40px">
            <div class="section-head">
                <span class="section-label">Още от</span>
                <h2 class="section-title"><?= e($product['category_name']) ?></h2>
            </div>
            <div class="products-grid">
                <?php foreach ($related as $p): ?>
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
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </div>
</main>

<?php include '../includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>