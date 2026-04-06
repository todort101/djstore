<?php
// stripe/create_cod_order.php
// Създава поръчка с наложен платеж (без Stripe)

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/cart.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Не си влязъл в акаунта си.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$shipping = [
    'name'    => trim($data['name']    ?? ''),
    'phone'   => trim($data['phone']   ?? ''),
    'address' => trim($data['address'] ?? ''),
];
$notes = trim($data['notes'] ?? '');

if (!$shipping['name'] || !$shipping['phone'] || !$shipping['address']) {
    echo json_encode(['error' => 'Моля попълни всички полета за доставка.']);
    exit;
}

$orderId = createOrder($_SESSION['user_id'], $shipping, $notes);

if ($orderId) {
    echo json_encode(['success' => true, 'order_id' => $orderId]);
} else {
    echo json_encode(['error' => 'Грешка при създаване на поръчката.']);
}