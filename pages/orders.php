<?php
// pages/orders.php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/cart.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();

$orders    = getOrdersByUser($_SESSION['user_id']);
$cartCount = getCartCount();

// Детайли на конкретна поръчка
$viewOrder = null;
$viewItems = [];
if (!empty($_GET['id'])) {
    $db   = getDB();
    $oid  = (int)$_GET['id'];
    $stmt = $db->prepare(
        "SELECT * FROM orders WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param('ii', $oid, $_SESSION['user_id']);
    $stmt->execute();
    $viewOrder = $stmt->get_result()->fetch_assoc();
    if ($viewOrder) {
        $viewItems = getOrderItems($oid);
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Моите поръчки — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="page-section">
    <div class="container">

        <?php if ($viewOrder): ?>
        <!-- ── Детайли на поръчка ──────────────────────────── -->
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:32px;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/pages/orders.php"
               class="btn btn--ghost btn--sm">← Назад</a>
            <h1 class="page-title" style="margin-bottom:0">
                ПОРЪЧКА #<?= $viewOrder['id'] ?>
            </h1>
        </div>

        <div class="cart-layout">
            <!-- Артикули -->
            <div>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Продукт</th>
                            <th>Цена</th>
                            <th>Бр.</th>
                            <th>Сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viewItems as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['product_slug'])): ?>
                                <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= e($item['product_slug']) ?>"
                                   style="color:var(--white);">
                                    <?= e($item['product_name']) ?>
                                </a>
                                <?php else: ?>
                                <?= e($item['product_name']) ?>
                                <?php endif; ?>
                            </td>
                            <td class="cart-price"><?= formatPrice($item['price']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td class="cart-price">
                                <?= formatPrice($item['price'] * $item['quantity']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Резюме -->
            <aside class="cart-summary">
                <h2 class="cart-summary__title">ДЕТАЙЛИ</h2>

                <div class="cart-summary__row">
                    <span>Номер</span>
                    <span>#<?= $viewOrder['id'] ?></span>
                </div>
                <div class="cart-summary__row">
                    <span>Статус</span>
                    <span class="status-badge status-<?= $viewOrder['status'] ?>">
                        <?= getOrderStatus($viewOrder['status']) ?>
                    </span>
                </div>
                <div class="cart-summary__row">
                    <span>Дата</span>
                    <span><?= date('d.m.Y H:i', strtotime($viewOrder['created_at'])) ?></span>
                </div>
                <div class="cart-summary__row">
                    <span>Получател</span>
                    <span><?= e($viewOrder['shipping_name']) ?></span>
                </div>
                <div class="cart-summary__row">
                    <span>Телефон</span>
                    <span><?= e($viewOrder['shipping_phone']) ?></span>
                </div>
                <div class="cart-summary__row">
                    <span>Адрес</span>
                    <span><?= e($viewOrder['shipping_address']) ?></span>
                </div>
                <?php if ($viewOrder['notes']): ?>
                <div class="cart-summary__row">
                    <span>Бележки</span>
                    <span><?= e($viewOrder['notes']) ?></span>
                </div>
                <?php endif; ?>
                <div class="cart-summary__total">
                    <span>ОБЩО</span>
                    <span><?= formatPrice($viewOrder['total_amount']) ?></span>
                </div>
            </aside>
        </div>

        <?php else: ?>
        <!-- ── Списък с поръчки ────────────────────────────── -->
        <h1 class="page-title">МОИТЕ ПОРЪЧКИ</h1>

        <?php showFlash(); ?>

        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-state__icon">📦</div>
            <h2 class="empty-state__title">Нямаш поръчки все още</h2>
            <p>Разгледай каталога и направи първата си поръчка!</p>
            <a href="<?= SITE_URL ?>/pages/catalog.php" class="btn btn--primary">
                Към каталога
            </a>
        </div>
        <?php else: ?>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Сума</th>
                    <th>Детайли</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><strong>#<?= $order['id'] ?></strong></td>
                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?= getOrderStatus($order['status']) ?>
                        </span>
                    </td>
                    <td class="cart-price"><?= formatPrice($order['total_amount']) ?></td>
                    <td>
                        <a href="?id=<?= $order['id'] ?>" class="btn-edit">
                            Виж →
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <?php endif; ?>

    </div>
</main>

<?php include '../includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>