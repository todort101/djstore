<?php
// stripe/save_shipping.php
// Запазва данните за доставка в сесията

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isLoggedIn()) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$_SESSION['checkout_shipping'] = [
    'name'    => trim($data['name']    ?? ''),
    'phone'   => trim($data['phone']   ?? ''),
    'address' => trim($data['address'] ?? ''),
    'notes'   => trim($data['notes']   ?? ''),
];

header('Content-Type: application/json');
echo json_encode(['ok' => true]);