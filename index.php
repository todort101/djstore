<?php
// index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/cart.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$categories    = getAllCategories();
$featuredProds = array_slice(getProducts(['sort' => 'newest']), 0, 8);
$cartCount     = getCartCount();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DJ Store — Професионална DJ Техника</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'includes/header.php'; ?>

<!-- ── HERO ──────────────────────────────────────────────────── -->
<section class="hero">
    <div class="hero__noise"></div>
    <div class="hero__grid"></div>
    <div class="hero__content">
        <span class="hero__label">Професионална техника</span>
        <h1 class="hero__title">ЗВУКЪТ<br><em>БЕЗ ГРАНИЦИ</em></h1>
        <p class="hero__sub">DJ конзоли, микшери, слушалки и говорители<br>от водещите световни марки.</p>
        <div class="hero__actions">
            <a href="<?= SITE_URL ?>/pages/catalog.php" class="btn btn--primary">Разгледай каталога</a>
            <a href="#categories" class="btn btn--ghost">Категории ↓</a>
        </div>
    </div>
    <div class="hero__visual">
        <div class="hero__circle hero__circle--1"></div>
        <div class="hero__circle hero__circle--2"></div>
        <div class="hero__circle hero__circle--3"></div>
        <div class="hero__waveform">
            <?php for ($i = 0; $i < 40; $i++): ?>
                <div class="bar" style="--h:<?= rand(20, 100) ?>%;--d:<?= $i * 0.04 ?>s"></div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- ── КАТЕГОРИИ ─────────────────────────────────────────────── -->
<section class="section categories-section" id="categories">
    <div class="container">
        <div class="section-head">
            <span class="section-label">Асортимент</span>
            <h2 class="section-title">КАТЕГОРИИ</h2>
        </div>
        <div class="categories-grid">
            <?php
            foreach ($categories as $cat): ?>
            <a href="<?= SITE_URL ?>/pages/catalog.php?category=<?= e($cat['slug']) ?>"
               class="cat-card">
                <span class="cat-card__icon"><?= e($cat['icon'] ?? '🎛️') ?></span>
                <span class="cat-card__name"><?= e($cat['name']) ?></span>
                <span class="cat-card__arrow">→</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ── BANNER ─────────────────────────────────────────────────── -->
<section class="banner-cta">
    <div class="container">
        <div class="banner-cta__inner">
            <div>
                <h2 class="banner-cta__title">Безплатна доставка<br>над €200</h2>
                <p class="banner-cta__sub">За цяла България с Speedy и Econt</p>
            </div>
            <a href="<?= SITE_URL ?>/pages/catalog.php" class="btn btn--primary">Пазарувай сега</a>
        </div>
    </div>
</section>

<!-- ── FEATURED PRODUCTS ─────────────────────────────────────── -->
<section class="section products-section">
    <div class="container">
        <div class="section-head">
            <span class="section-label">Топ избор</span>
            <h2 class="section-title">НОВИ ПРОДУКТИ</h2>
            <a href="<?= SITE_URL ?>/pages/catalog.php" class="section-link">Виж всички →</a>
        </div>
        <div class="products-grid">
            <?php foreach ($featuredProds as $p): ?>
            <div class="product-card">
                <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= e($p['slug']) ?>"
                   class="product-card__img-wrap">
                    <img src="<?= productImage($p['image']) ?>"
                         alt="<?= e($p['name']) ?>"
                         loading="lazy">
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
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>