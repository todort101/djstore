<?php
// pages/payment_success.php
$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/cart.php';
require_once $root . '/vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$orderId = null;
$method  = $_GET['method'] ?? 'card';

// ── Наложен платеж — поръчката вече е създадена ───────────────
if ($method === 'cod' && !empty($_GET['order_id'])) {
    $orderId = (int)$_GET['order_id'];
}

// ── Stripe плащане — проверяваме payment_intent ───────────────
if ($method !== 'cod') {
    $paymentIntentId = $_GET['payment_intent'] ?? null;

    if (!$paymentIntentId) {
        setFlash('error', 'Невалидна заявка за плащане.');
        header('Location: ' . SITE_URL . '/pages/cart.php');
        exit;
    }

    try {
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

        if ($paymentIntent->status === 'succeeded') {
            // Вземи данните за доставка от сесията
            $shipping = $_SESSION['checkout_shipping'] ?? [
                'name'    => $_GET['name']    ?? '',
                'phone'   => $_GET['phone']   ?? '',
                'address' => $_GET['address'] ?? '',
            ];
            $notes = $_SESSION['checkout_shipping']['notes'] ?? $_GET['notes'] ?? '';

            if (!empty($shipping['name']) && !empty($shipping['address'])) {
                // Създай поръчката само ако не е създадена
                if (empty($_SESSION['stripe_order_created_' . $paymentIntentId])) {
                    $orderId = createOrder($_SESSION['user_id'], $shipping, $notes);
                    if ($orderId) {
                        // Запази stripe payment id в поръчката
                        $db   = getDB();
                        $stmt = $db->prepare(
                            "UPDATE orders SET notes = CONCAT(IFNULL(notes,''), ' | Stripe: ', ?) WHERE id = ?"
                        );
                        $stmt->bind_param('si', $paymentIntentId, $orderId);
                        $stmt->execute();
                        $_SESSION['stripe_order_created_' . $paymentIntentId] = true;
                    }
                }
            }
        } else {
            setFlash('error', 'Плащането не е завършено. Статус: ' . $paymentIntent->status);
            header('Location: ' . SITE_URL . '/pages/checkout.php');
            exit;
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        setFlash('error', 'Грешка при проверка на плащането.');
        header('Location: ' . SITE_URL . '/pages/checkout.php');
        exit;
    }

    // Изчисти сесийните данни
    unset($_SESSION['checkout_shipping']);
    unset($_SESSION['payment_intent_id']);
}

$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Успешна поръчка — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        html, body { background: #0a0a0a !important; color: #f5f5f0 !important; min-height: 100vh; }

        .success-page {
            min-height: calc(100vh - var(--header-h));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px clamp(16px,4vw,48px);
        }
        .success-box {
            max-width: 560px;
            width: 100%;
            text-align: center;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(0,204,102,.12);
            border: 3px solid var(--success);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            margin: 0 auto 28px;
            animation: popIn .5s cubic-bezier(.175,.885,.32,1.275) both;
        }
        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to   { transform: scale(1); opacity: 1; }
        }
        .success-title {
            font-size: clamp(2.5rem,6vw,4.5rem);
            color: var(--white);
            margin-bottom: 12px;
        }
        .success-sub {
            color: var(--grey-light);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 32px;
        }
        .success-order {
            display: inline-block;
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: var(--radius-lg);
            padding: 20px 32px;
            margin-bottom: 32px;
            width: 100%;
        }
        .success-order__label {
            font-family: var(--font-condensed);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .15em;
            text-transform: uppercase;
            color: var(--grey);
            margin-bottom: 6px;
        }
        .success-order__num {
            font-family: var(--font-display);
            font-size: 2.5rem;
            color: var(--accent);
        }
        .success-method {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0,204,102,.08);
            border: 1px solid rgba(0,204,102,.2);
            border-radius: 99px;
            padding: 6px 16px;
            font-size: .85rem;
            color: var(--success);
            margin-bottom: 32px;
            font-family: var(--font-condensed);
            font-weight: 700;
            letter-spacing: .06em;
        }
        .success-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        /* Confetti */
        .confetti-wrap {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 9999;
        }
        .confetti-piece {
            position: absolute;
            top: -20px;
            width: 8px;
            height: 14px;
            border-radius: 2px;
            opacity: 0;
            animation: confettiFall linear forwards;
        }
        @keyframes confettiFall {
            0%   { opacity: 1; transform: translateY(0) rotate(0deg); }
            100% { opacity: 0; transform: translateY(110vh) rotate(720deg); }
        }
    </style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<!-- Confetti -->
<div class="confetti-wrap" id="confetti"></div>

<main class="success-page">
    <div class="success-box">

        <div class="success-icon">✓</div>

        <h1 class="success-title">ПОРЪЧКАТА<br>Е ПРИЕТА!</h1>

        <p class="success-sub">
            Благодарим ти за покупката!<br>
            Ще се свържем с теб скоро за потвърждение.
        </p>

        <?php if ($orderId): ?>
        <div class="success-order">
            <p class="success-order__label">Номер на поръчка</p>
            <p class="success-order__num">#<?= $orderId ?></p>
        </div>
        <?php endif; ?>

        <div class="success-method">
            <?php if ($method === 'cod'): ?>
                💵 Наложен платеж — плащане при доставка
            <?php else: ?>
                💳 Платено с карта чрез Stripe
            <?php endif; ?>
        </div>

        <div class="success-actions">
            <?php if ($orderId): ?>
            <a href="<?= SITE_URL ?>/pages/orders.php?id=<?= $orderId ?>"
               class="btn btn--primary">
                📦 Виж поръчката
            </a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/pages/catalog.php" class="btn btn--ghost">
                Продължи пазаруването
            </a>
        </div>

    </div>
</main>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
// Confetti анимация
(function() {
    const colors  = ['#e8ff00','#ffffff','#00cc66','#4af','#ff4444'];
    const wrap    = document.getElementById('confetti');
    const count   = 80;

    for (let i = 0; i < count; i++) {
        const el = document.createElement('div');
        el.className = 'confetti-piece';
        el.style.cssText = [
            'left:'              + Math.random() * 100 + '%',
            'background:'        + colors[Math.floor(Math.random() * colors.length)],
            'animation-duration:'+ (2 + Math.random() * 3) + 's',
            'animation-delay:'   + (Math.random() * 1.5) + 's',
            'transform:rotate('  + Math.random() * 360 + 'deg)',
            'width:'             + (6 + Math.random() * 8) + 'px',
            'height:'            + (10 + Math.random() * 10) + 'px',
        ].join(';');
        wrap.appendChild(el);
    }

    // Изчисти след 5 секунди
    setTimeout(() => wrap.remove(), 5000);
})();
</script>
</body>
</html>