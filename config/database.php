<?php
// config/database.php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'djstore');
define('SITE_URL', 'http://localhost/djstore');
define('SITE_NAME', 'DJ Store');
define('UPLOAD_DIR', dirname(__DIR__) . '/admin/uploads/');
define('UPLOAD_URL', SITE_URL . '/admin/uploads/');

// ── Stripe ───────────────────────────────────────────────────
define('STRIPE_PUBLIC_KEY', 'pk_test_51TCftJGzcgo9XMAnTWfg0LbBSDdD7EX3gzHQf19kQZcmE3o4vRr1EdR7CYBLOMlo5mBxD9ErMJkHVZrgWCSjKBWl00Tgj7VDs0');
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY']);
define('STRIPE_CURRENCY',   'eur');

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        if ($conn->connect_error) {
            die('Грешка при свързване с базата данни: ' . $conn->connect_error);
        }
    }
    return $conn;
}
