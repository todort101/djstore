<?php
// pages/checkout.php
$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/cart.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Трябва да си логнат
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = SITE_URL . '/pages/checkout.php';
    setFlash('info', 'Влез в акаунта си, за да продължиш с поръчката.');
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit;
}

$cartDetails = getCartDetails();
if (empty($cartDetails['items'])) {
    setFlash('warning', 'Кошницата е празна.');
    header('Location: ' . SITE_URL . '/pages/cart.php');
    exit;
}

$user     = getCurrentUser();
$shipping = $cartDetails['shipping'];
$total    = $cartDetails['total'];
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поръчка — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <!-- Stripe JS -->
    <script src="https://js.stripe.com/v3/" crossorigin="anonymous"></script>
    <style>
        html, body { background: #0a0a0a !important; color: #f5f5f0 !important; min-height: 100vh; }

        /* ── Stripe Payment Element стилове ── */
        #payment-form { width: 100%; }

        #payment-element {
            background: var(--dark-3);
            border: 1px solid var(--dark-4);
            border-radius: var(--radius);
            padding: 16px;
            margin-bottom: 20px;
            transition: border-color var(--transition);
        }
        #payment-element:focus-within {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(232,255,0,.1);
        }

        #payment-message {
            display: none;
            padding: 14px 20px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-size: .95rem;
            border-left: 4px solid var(--danger);
            background: rgba(255,68,68,.1);
            color: var(--danger);
        }
        #payment-message.show { display: block; }

        #submit-btn {
            position: relative;
            width: 100%;
        }
        #submit-btn .btn-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(0,0,0,.3);
            border-top-color: var(--black);
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        #submit-btn.loading .btn-text  { display: none; }
        #submit-btn.loading .btn-spinner { display: inline-block; }
        #submit-btn:disabled { opacity: .7; cursor: not-allowed; }

        @keyframes spin { to { transform: rotate(360deg); } }

        .payment-methods-icons {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 12px;
            flex-wrap: wrap;
        }
        .payment-methods-icons span {
            background: var(--dark-3);
            border: 1px solid var(--dark-4);
            border-radius: 4px;
            padding: 4px 10px;
            font-size: .75rem;
            color: var(--grey-light);
            font-family: var(--font-condensed);
            font-weight: 700;
            letter-spacing: .05em;
        }

        .secure-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--grey);
            font-size: .8rem;
            margin-top: 12px;
            justify-content: center;
        }

        /* Стъпки */
        .checkout-steps {
            display: flex;
            align-items: center;
            gap: 0;
            margin-bottom: 40px;
        }
        .checkout-step {
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-condensed);
            font-weight: 700;
            font-size: .85rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--grey);
        }
        .checkout-step.active { color: var(--accent); }
        .checkout-step.done   { color: var(--success); }
        .checkout-step__num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid currentColor;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
            flex-shrink: 0;
        }
        .checkout-divider {
            flex: 1;
            height: 1px;
            background: var(--dark-4);
            margin: 0 12px;
            max-width: 60px;
        }

        /* Табове */
        .payment-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            border: 1px solid var(--dark-4);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .payment-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            font-family: var(--font-condensed);
            font-weight: 700;
            font-size: .9rem;
            letter-spacing: .05em;
            color: var(--grey-light);
            background: var(--dark-3);
            cursor: pointer;
            border: none;
            transition: all var(--transition);
            border-right: 1px solid var(--dark-4);
        }
        .payment-tab:last-child { border-right: none; }
        .payment-tab.active {
            background: var(--accent);
            color: var(--black);
        }
        .payment-tab:hover:not(.active) {
            background: var(--dark-4);
            color: var(--white);
        }

        .payment-panel { display: none; }
        .payment-panel.active { display: block; }
    </style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<main class="page-section">
    <div class="container">
        <h1 class="page-title">ЗАВЪРШИ ПОРЪЧКАТА</h1>

        <!-- Прогрес стъпки -->
        <div class="checkout-steps" style="margin-bottom:40px;">
            <div class="checkout-step done">
                <div class="checkout-step__num">✓</div>
                <span>Кошница</span>
            </div>
            <div class="checkout-divider"></div>
            <div class="checkout-step active">
                <div class="checkout-step__num">2</div>
                <span>Доставка</span>
            </div>
            <div class="checkout-divider"></div>
            <div class="checkout-step active">
                <div class="checkout-step__num">3</div>
                <span>Плащане</span>
            </div>
        </div>

        <?php showFlash(); ?>

        <div class="cart-layout">

            <!-- ── ЛЯВА КОЛОНА: Доставка + Плащане ── -->
            <div>

                <!-- Данни за доставка -->
                <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                            border-radius:var(--radius-lg);padding:32px;margin-bottom:24px;">
                    <h2 style="font-size:1.5rem;color:var(--white);margin-bottom:24px;
                               padding-bottom:16px;border-bottom:1px solid var(--dark-4);">
                        📦 Данни за доставка
                    </h2>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label for="shipping_name">Три имена</label>
                            <input type="text"
                                   id="shipping_name"
                                   placeholder="Иван Петров Иванов"
                                   value="<?= e($user['full_name']) ?>"
                                   required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_phone">Телефон</label>
                            <input type="tel"
                                   id="shipping_phone"
                                   placeholder="0888 123 456"
                                   value="<?= e($user['phone'] ?? '') ?>"
                                   required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="shipping_address">Адрес за доставка</label>
                        <textarea id="shipping_address"
                                  rows="2"
                                  placeholder="гр. София, ул. Примерна 1, ет. 2, ап. 5"
                                  required><?= e($user['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label for="order_notes">Бележки (незадължително)</label>
                        <textarea id="order_notes"
                                  rows="2"
                                  placeholder="Специални инструкции..."></textarea>
                    </div>
                </div>

                <!-- Начин на плащане -->
                <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                            border-radius:var(--radius-lg);padding:32px;">
                    <h2 style="font-size:1.5rem;color:var(--white);margin-bottom:24px;
                               padding-bottom:16px;border-bottom:1px solid var(--dark-4);">
                        💳 Начин на плащане
                    </h2>

                    <!-- Табове -->
                    <div class="payment-tabs">
                        <button class="payment-tab active" onclick="switchTab('card')">
                            💳 Карта
                        </button>
                        <button class="payment-tab" onclick="switchTab('cod')">
                            💵 Наложен платеж
                        </button>
                    </div>

                    <!-- Stripe карта -->
                    <div class="payment-panel active" id="panel-card">
                        <div id="payment-message"></div>
                        <div id="payment-element"></div>

                        <div class="payment-methods-icons">
                            <span>VISA</span>
                            <span>Mastercard</span>
                            <span>American Express</span>
                            <span>Apple Pay</span>
                            <span>Google Pay</span>
                        </div>

                        <button id="submit-btn"
                                class="btn btn--primary btn--full"
                                style="margin-top:20px;padding:16px;font-size:1.1rem;">
                            <span class="btn-text">
                                🔒 Плати <?= formatPrice($total) ?>
                            </span>
                            <span class="btn-spinner"></span>
                        </button>

                        <div class="secure-badge">
                            🔒 Плащането е защитено от Stripe — 256-bit SSL криптиране
                        </div>
                    </div>

                    <!-- Наложен платеж -->
                    <div class="payment-panel" id="panel-cod">
                        <div style="padding:24px;background:var(--dark-3);
                                    border-radius:var(--radius);margin-bottom:20px;">
                            <p style="color:var(--white);margin-bottom:8px;">
                                <strong>💵 Плащане при доставка</strong>
                            </p>
                            <p style="color:var(--grey);font-size:.9rem;line-height:1.6;">
                                Плащате в брой на куриера при получаване на пратката.
                                Куриерската фирма ще се свърже с вас за потвърждение.
                            </p>
                        </div>
                        <button id="cod-btn"
                                class="btn btn--primary btn--full"
                                style="padding:16px;font-size:1.1rem;">
                            ✅ Потвърди поръчката
                        </button>
                    </div>

                </div>
            </div>

            <!-- ── ДЯСНА КОЛОНА: Резюме ── -->
            <aside class="cart-summary">
                <h2 class="cart-summary__title">ПОРЪЧКА</h2>

                <?php foreach ($cartDetails['items'] as $item): ?>
                <div class="cart-summary__row">
                    <span>
                        <?= e($item['product']['name']) ?>
                        <small style="color:var(--grey)"> × <?= $item['quantity'] ?></small>
                    </span>
                    <span><?= formatPrice($item['subtotal']) ?></span>
                </div>
                <?php endforeach; ?>

                <div class="cart-summary__row">
                    <span>Доставка</span>
                    <span style="color:var(--success)">
                        <?= $shipping == 0 ? 'Безплатна' : formatPrice($shipping) ?>
                    </span>
                </div>

                <div class="cart-summary__total">
                    <span>ОБЩО</span>
                    <span><?= formatPrice($total) ?></span>
                </div>

                <div style="margin-top:20px;padding-top:16px;
                            border-top:1px solid var(--dark-4);">
                    <!-- Продукти резюме -->
                    <?php foreach ($cartDetails['items'] as $item): ?>
                    <div style="display:flex;gap:10px;align-items:center;
                                margin-bottom:12px;">
                        <img src="<?= productImage($item['product']['image']) ?>"
                             alt=""
                             style="width:44px;height:44px;object-fit:contain;
                                    background:var(--dark-3);border-radius:4px;
                                    padding:4px;flex-shrink:0;">
                        <div>
                            <p style="font-size:.85rem;color:var(--white);line-height:1.3;">
                                <?= e($item['product']['name']) ?>
                            </p>
                            <p style="font-size:.75rem;color:var(--grey);">
                                <?= $item['quantity'] ?> бр. × <?= formatPrice($item['product']['price']) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <a href="<?= SITE_URL ?>/pages/cart.php"
                       class="btn btn--ghost btn--sm btn--full"
                       style="margin-top:8px;">
                        ← Редактирай кошницата
                    </a>
                </div>
            </aside>

        </div><!-- /cart-layout -->
    </div>
</main>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>

<script>
// ── Конфигурация ─────────────────────────────────────────────
const STRIPE_PK  = '<?= STRIPE_PUBLIC_KEY ?>';
const CREATE_URL = '<?= SITE_URL ?>/stripe/create_payment.php';
const SUCCESS_URL = '<?= SITE_URL ?>/pages/payment_success.php';
const COD_URL     = '<?= SITE_URL ?>/pages/checkout_cod.php';

// ── Инициализация ─────────────────────────────────────────────
const stripe  = Stripe(STRIPE_PK);
let elements  = null;
let clientSecret = null;
let currentTab = 'card';

// Зареди Stripe Payment Element веднага
initStripe();

async function initStripe() {
    try {
        const res  = await fetch(CREATE_URL, { method: 'POST' });
        const data = await res.json();

        if (data.error) {
            showMessage(data.error);
            return;
        }

        clientSecret = data.clientSecret;

        elements = stripe.elements({
            clientSecret,
            appearance: {
                theme: 'night',
                variables: {
                    colorPrimary:       '#e8ff00',
                    colorBackground:    '#1a1a1a',
                    colorText:          '#f5f5f0',
                    colorDanger:        '#ff4444',
                    fontFamily:         'Barlow, sans-serif',
                    borderRadius:       '4px',
                    spacingUnit:        '4px',
                },
                rules: {
                    '.Input': {
                        backgroundColor: '#242424',
                        border:          '1px solid #2e2e2e',
                        color:           '#f5f5f0',
                    },
                    '.Input:focus': {
                        border:     '1px solid #e8ff00',
                        boxShadow:  '0 0 0 3px rgba(232,255,0,0.1)',
                    },
                    '.Label': { color: '#cccccc' },
                }
            }
        });

        const paymentElement = elements.create('payment');
        paymentElement.mount('#payment-element');

    } catch (err) {
        showMessage('Грешка при зареждане на платежната форма.');
        console.error(err);
    }
}

// ── Смяна на таб ─────────────────────────────────────────────
function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.payment-tab').forEach((btn, i) => {
        btn.classList.toggle('active', (i === 0 && tab === 'card') || (i === 1 && tab === 'cod'));
    });
    document.getElementById('panel-card').classList.toggle('active', tab === 'card');
    document.getElementById('panel-cod').classList.toggle('active',  tab === 'cod');
}

// ── Валидация на доставка ─────────────────────────────────────
function validateShipping() {
    const name    = document.getElementById('shipping_name').value.trim();
    const phone   = document.getElementById('shipping_phone').value.trim();
    const address = document.getElementById('shipping_address').value.trim();

    if (!name)    { showMessage('Моля въведи три имена.'); return false; }
    if (!phone)   { showMessage('Моля въведи телефон.'); return false; }
    if (!address) { showMessage('Моля въведи адрес за доставка.'); return false; }
    return true;
}

function getShippingData() {
    return {
        name:    document.getElementById('shipping_name').value.trim(),
        phone:   document.getElementById('shipping_phone').value.trim(),
        address: document.getElementById('shipping_address').value.trim(),
        notes:   document.getElementById('order_notes').value.trim(),
    };
}

// ── Stripe плащане ────────────────────────────────────────────
document.getElementById('submit-btn').addEventListener('click', async () => {
    if (!validateShipping()) return;

    const btn = document.getElementById('submit-btn');
    btn.classList.add('loading');
    btn.disabled = true;
    hideMessage();

    // Запази данните за доставка в сесията чрез AJAX
    const shipping = getShippingData();
    await saveShipping(shipping);

    const returnUrl = SUCCESS_URL +
        '?name='    + encodeURIComponent(shipping.name) +
        '&phone='   + encodeURIComponent(shipping.phone) +
        '&address=' + encodeURIComponent(shipping.address) +
        '&notes='   + encodeURIComponent(shipping.notes);

    const { error } = await stripe.confirmPayment({
        elements,
        confirmParams: {
            return_url: returnUrl,
            payment_method_data: {
                billing_details: {
                    name:  shipping.name,
                    phone: shipping.phone,
                }
            }
        },
    });

    // Ако стигнем тук — има грешка (успехът се handle-ва от return_url)
    if (error) {
        if (error.type === 'card_error' || error.type === 'validation_error') {
            showMessage(error.message);
        } else {
            showMessage('Възникна неочаквана грешка. Опитай отново.');
        }
    }

    btn.classList.remove('loading');
    btn.disabled = false;
});

// ── Наложен платеж ────────────────────────────────────────────
document.getElementById('cod-btn').addEventListener('click', async () => {
    if (!validateShipping()) return;

    const btn = document.getElementById('cod-btn');
    btn.disabled = true;
    btn.textContent = 'Обработва се...';

    const shipping = getShippingData();

    const res  = await fetch('<?= SITE_URL ?>/stripe/create_cod_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(shipping),
    });
    const data = await res.json();

    if (data.success) {
        window.location.href = SUCCESS_URL + '?order_id=' + data.order_id + '&method=cod';
    } else {
        showMessage(data.error || 'Грешка при създаване на поръчката.');
        btn.disabled = false;
        btn.textContent = 'Потвърди поръчката';
    }
});

// ── Запази данните за доставка ────────────────────────────────
async function saveShipping(data) {
    try {
        await fetch('<?= SITE_URL ?>/stripe/save_shipping.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(data),
        });
    } catch(e) { /* не е критично */ }
}

// ── Съобщения ──────────────────────────────────────────────────
function showMessage(msg) {
    const el = document.getElementById('payment-message');
    el.textContent = msg;
    el.classList.add('show');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function hideMessage() {
    document.getElementById('payment-message').classList.remove('show');
}
</script>

</body>
</html>