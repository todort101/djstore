<?php
// pages/cart.php
$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/cart.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Обработка на действия ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action']     ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);
    $redirect  = $_POST['redirect']   ?? '';

    switch ($action) {

        case 'add':
            $qty = max(1, (int)($_POST['quantity'] ?? 1));
            addToCart($productId, $qty);
            setFlash('success', 'Продуктът е добавен в кошницата!');
            if ($redirect === 'catalog') {
                header('Location: ' . SITE_URL . '/pages/catalog.php');
            } else {
                header('Location: ' . SITE_URL . '/pages/cart.php');
            }
            exit;

        case 'update':
            $qty = (int)($_POST['quantity'] ?? 0);
            updateCart($productId, $qty);
            header('Location: ' . SITE_URL . '/pages/cart.php');
            exit;

        case 'remove':
            removeFromCart($productId);
            setFlash('info', 'Продуктът е премахнат от кошницата.');
            header('Location: ' . SITE_URL . '/pages/cart.php');
            exit;

        case 'clear':
            clearCart();
            setFlash('info', 'Кошницата е изчистена.');
            header('Location: ' . SITE_URL . '/pages/cart.php');
            exit;

        // ── Приложи промо код ─────────────────────────────
        case 'apply_promo':
            $code        = strtoupper(trim($_POST['promo_code'] ?? ''));
            $cartDetails = getCartDetails();
            $userId      = isLoggedIn() ? $_SESSION['user_id'] : null;

            if (empty($code)) {
                setFlash('error', 'Моля въведи промо код.');
            } elseif (empty($cartDetails['items'])) {
                setFlash('error', 'Кошницата е празна.');
            } else {
                $result = validatePromoCode($code, $cartDetails['subtotal'], $userId);
                if ($result['valid']) {
                    setSessionPromo($result['promo']);
                    $label = $result['label'];
                    setFlash('success', 'Промо кодът е приложен! Отстъпка: ' . $label);
                } else {
                    setFlash('error', $result['error']);
                }
            }
            header('Location: ' . SITE_URL . '/pages/cart.php');
            exit;

        case 'remove_promo':
    // Изчистваме абсолютно всичко свързано с промо кодове в сесията
    unset($_SESSION['promo']); 
    unset($_SESSION['promo_code']);
    unset($_SESSION['discount_amount']);
    unset($_SESSION['promo_id']);
    
    // Ако имаш специфична функция в includes/cart.php за това - използвай я
    if (function_exists('clearPromo')) {
        clearPromo();
    }

    setFlash('info', 'Промо кодът е премахнат.');
    header('Location: ' . SITE_URL . '/pages/cart.php');
    exit;
    }
}

$cartDetails = getCartDetails();
$cartCount   = getCartCount();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кошница — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        html, body { background: #0a0a0a !important; color: #f5f5f0 !important; min-height: 100vh; }

        /* Промо секция */
        .promo-box {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            margin-bottom: 16px;
        }
        .promo-box__title {
            font-family: var(--font-condensed);
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .15em;
            text-transform: uppercase;
            color: var(--grey);
            margin-bottom: 12px;
        }
        .promo-form {
            display: flex;
            gap: 10px;
        }
        .promo-form input {
            flex: 1;
            padding: 10px 16px;
            background: var(--dark-3);
            border: 1px solid var(--dark-4);
            border-radius: var(--radius);
            color: var(--white);
            font-family: var(--font-condensed);
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            transition: border-color var(--transition);
        }
        .promo-form input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(232,255,0,.1);
        }
        .promo-form input::placeholder {
            text-transform: none;
            font-weight: 400;
            letter-spacing: 0;
            color: var(--grey);
        }

        /* Активен промо код */
        .promo-active {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: rgba(0,204,102,.08);
            border: 1px solid rgba(0,204,102,.25);
            border-radius: var(--radius);
            padding: 12px 16px;
        }
        .promo-active__info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .promo-active__code {
            font-family: var(--font-condensed);
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .12em;
            color: var(--success);
        }
        .promo-active__label {
            font-size: .85rem;
            color: var(--grey-light);
        }
        .promo-active__remove {
            background: none;
            border: 1px solid rgba(255,68,68,.3);
            border-radius: var(--radius);
            color: var(--danger);
            padding: 4px 10px;
            font-size: .75rem;
            font-family: var(--font-condensed);
            font-weight: 700;
            cursor: pointer;
            transition: all var(--transition);
        }
        .promo-active__remove:hover {
            background: rgba(255,68,68,.1);
        }

        /* Discount ред в summary */
        .cart-summary__discount {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: .95rem;
            color: var(--success);
            border-bottom: 1px solid var(--dark-3);
        }
    </style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<main class="page-section">
    <div class="container">
        <h1 class="page-title">КОШНИЦА</h1>

        <?php showFlash(); ?>

        <?php if (empty($cartDetails['items'])): ?>
        <!-- Празна кошница -->
        <div class="empty-state">
            <div class="empty-state__icon">🛒</div>
            <h2 class="empty-state__title">Кошницата е празна</h2>
            <p>Разгледай каталога и добави продукти.</p>
            <a href="<?= SITE_URL ?>/pages/catalog.php" class="btn btn--primary">
                Към каталога
            </a>
        </div>

        <?php else: ?>
        <div class="cart-layout">

            <!-- ── Артикули ───────────────────────────── -->
            <div>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Продукт</th>
                            <th>Цена</th>
                            <th>Количество</th>
                            <th>Сума</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartDetails['items'] as $item):
                            $p = $item['product']; ?>
                        <tr>
                            <td>
                                <div class="cart-product">
                                    <img class="cart-product-img"
                                         src="<?= productImage($p['image']) ?>"
                                         alt="<?= e($p['name']) ?>">
                                    <a href="<?= SITE_URL ?>/pages/product.php?slug=<?= e($p['slug'] ?? '') ?>"
                                       class="cart-product-name">
                                        <?= e($p['name']) ?>
                                    </a>
                                </div>
                            </td>
                            <td class="cart-price"><?= formatPrice($p['price']) ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action"     value="update">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="number"
                                           name="quantity"
                                           value="<?= $item['quantity'] ?>"
                                           min="0"
                                           max="<?= $p['stock'] ?>"
                                           class="cart-qty-input">
                                </form>
                            </td>
                            <td class="cart-price"><?= formatPrice($item['subtotal']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="action"     value="remove">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-delete"
                                            data-confirm="Премахни продукта от кошницата?">
                                        ✕
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Изчисти кошницата -->
                <div style="margin-top:16px;text-align:right;">
                    <form method="POST">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn--ghost btn--sm"
                                data-confirm="Сигурен ли си, че искаш да изчистиш кошницата?">
                            🗑 Изчисти кошницата
                        </button>
                    </form>
                </div>

                <!-- ── Промо код ─────────────────────── -->
                <div class="promo-box" style="margin-top:24px;">
                    <p class="promo-box__title">🏷️ Промо код</p>

                    <?php $activePromo = $cartDetails['promo']; ?>

                    <?php if ($activePromo): ?>
                    <!-- Активен промо код -->
                    <div class="promo-active">
                        <div class="promo-active__info">
                            <span style="font-size:1.3rem;">🎉</span>
                            <div>
                                <p class="promo-active__code">
                                    <?= e($activePromo['code']) ?>
                                </p>
                                <p class="promo-active__label">
                                    <?php if ($activePromo['type'] === 'percent'): ?>
                                        Отстъпка <?= $activePromo['value'] ?>% приложена
                                    <?php else: ?>
                                        Отстъпка <?= formatPrice($activePromo['value']) ?> приложена
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="remove_promo">
                            <button type="submit" class="promo-active__remove">
                                ✕ Премахни
                            </button>
                        </form>
                    </div>

                    <?php else: ?>
                    <!-- Форма за промо код -->
                    <form method="POST" class="promo-form">
                        <input type="hidden" name="action" value="apply_promo">
                        <input type="text"
                               name="promo_code"
                               placeholder="Въведи промо код..."
                               maxlength="50"
                               autocomplete="off">
                        <button type="submit" class="btn btn--ghost">
                            Приложи
                        </button>
                    </form>
                    <p style="color:var(--grey);font-size:.8rem;margin-top:8px;">
                        Имаш промо код? Въведи го тук за отстъпка.
                    </p>
                    <?php endif; ?>

                </div>
            </div>

            <!-- ── Обобщение ──────────────────────────── -->
            <aside class="cart-summary">
                <h2 class="cart-summary__title">ОБОБЩЕНИЕ</h2>

                <!-- Артикули -->
                <?php foreach ($cartDetails['items'] as $item): ?>
                <div class="cart-summary__row">
                    <span><?= e($item['product']['name']) ?> × <?= $item['quantity'] ?></span>
                    <span><?= formatPrice($item['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>

                <!-- Subtotal -->
                <div class="cart-summary__row">
                    <span>Междинна сума</span>
                    <span><?= formatPrice($cartDetails['subtotal']) ?></span>
                </div>

                <!-- Отстъпка -->
                <?php if ($cartDetails['discount'] > 0): ?>
                <div class="cart-summary__discount">
                    <span>
                        🏷️ <?= e($cartDetails['promo']['code']) ?>
                        <?php if ($cartDetails['promo']['type'] === 'percent'): ?>
                            (-<?= $cartDetails['promo']['value'] ?>%)
                        <?php endif; ?>
                    </span>
                    <span>-<?= formatPrice($cartDetails['discount']) ?></span>
                </div>
                <?php endif; ?>

                <!-- Доставка -->
                <div class="cart-summary__row">
                    <span>Доставка</span>
                    <span style="color:var(--success)">
                        <?= $cartDetails['shipping'] == 0 ? 'Безплатна' : formatPrice($cartDetails['shipping']) ?>
                    </span>
                </div>

                <!-- Общо -->
                <div class="cart-summary__total">
                    <span>ОБЩО</span>
                    <span><?= formatPrice($cartDetails['total']) ?></span>
                </div>

                <?php if ($cartDetails['discount'] > 0): ?>
                <div style="text-align:center;margin-top:8px;">
                    <span style="color:var(--success);font-size:.85rem;font-family:var(--font-condensed);font-weight:700;">
                        ✓ Спестяваш <?= formatPrice($cartDetails['discount']) ?>!
                    </span>
                </div>
                <?php endif; ?>

                <div style="margin-top:24px;display:flex;flex-direction:column;gap:10px;">
                    <a href="<?= SITE_URL ?>/pages/checkout.php"
                       class="btn btn--primary btn--full">
                        Продължи към поръчка →
                    </a>
                    <a href="<?= SITE_URL ?>/pages/catalog.php"
                       class="btn btn--ghost btn--full">
                        ← Продължи пазаруването
                    </a>
                </div>
            </aside>

        </div><!-- /cart-layout -->
        <?php endif; ?>

    </div>
</main>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>