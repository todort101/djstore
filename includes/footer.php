<?php // includes/footer.php ?>
<footer class="site-footer">
    <div class="container footer__inner">
        <div class="footer__brand">
            <a href="<?= SITE_URL ?>" class="logo">
                <span class="logo__icon">⬡</span>
                <span class="logo__text">DJ<strong>STORE</strong></span>
            </a>
            <p>Професионална DJ техника<br>от водещите световни марки.</p>
        </div>
        <div class="footer__col">
            <h4>Каталог</h4>
            <?php foreach (getAllCategories() as $cat): ?>
            <a href="<?= SITE_URL ?>/pages/catalog.php?category=<?= e($cat['slug']) ?>">
                <?= e($cat['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="footer__col">
            <h4>Акаунт</h4>
            <a href="<?= SITE_URL ?>/pages/register.php">Регистрация</a>
            <a href="<?= SITE_URL ?>/pages/login.php">Вход</a>
            <a href="<?= SITE_URL ?>/pages/orders.php">Моите поръчки</a>
            <a href="<?= SITE_URL ?>/pages/privacy.php">Политика на поверителност</a>
            <a href="<?= SITE_URL ?>/pages/terms.php">Условия за ползване</a>
        </div>
        <div class="footer__col">
            <h4>Информация</h4>
            <p>📧 info@djstore.bg</p>
            <p>📞 0800 123 456</p>
            <p>🕒 Пон–Пет: 9:00–18:00</p>
        </div>
    </div>
    <div class="footer__bottom">
        <div class="container">
            <p>© <?= date('Y') ?> DJ Store. Дипломна работа.</p>
        </div>
    </div>
</footer>