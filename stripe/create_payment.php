<?php
// stripe/create_payment.php
// Backend endpoint — създава Stripe PaymentIntent

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/cart.php';
require_once $root . '/vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Само POST заявки
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Само логнати потребители
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Не си влязъл в акаунта си.']);
    exit;
}

// Вземи кошницата
$cartDetails = getCartDetails();
if (empty($cartDetails['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Кошницата е празна.']);
    exit;
}

// Изчисли сумата
$shipping = $cartDetails['total'] >= 200 ? 0 : 8.99;
$total    = $cartDetails['total'] + $shipping;

// Stripe изисква сумата в стотинки (cents)
$amountInCents = (int)round($total * 100);

try {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount'   => $amountInCents,
        'currency' => STRIPE_CURRENCY,
        'metadata' => [
            'user_id'  => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
        ],
        'automatic_payment_methods' => [
            'enabled' => true,
        ],
    ]);

    // Запази payment_intent_id в сесията за по-късна проверка
    $_SESSION['payment_intent_id'] = $paymentIntent->id;

    header('Content-Type: application/json');
    echo json_encode([
        'clientSecret' => $paymentIntent->client_secret,
        'amount'       => $total,
        'amountCents'  => $amountInCents,
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}