<?php
// pages/register.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/cart.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (isLoggedIn()) { header('Location: ' . SITE_URL); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = registerUser($_POST);
    if ($result['success']) {
        setFlash('success', 'Регистрацията е успешна! Можеш да влезеш в акаунта си.');
        header('Location: ' . SITE_URL . '/pages/login.php');
        exit;
    }
    $error = $result['error'];
}
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        html, body { background: #0a0a0a !important; color: #f5f5f0 !important; min-height: 100vh; }
    </style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<main class="form-page">
    <div class="form-box">
        <h1 class="form-box__title">РЕГИСТРАЦИЯ</h1>
        <p class="form-box__sub">Създай акаунт и следи своите поръчки</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="full_name">Пълно ime</label>
                <input type="text"
                       id="full_name"
                       name="full_name"
                       value="<?= e($_POST['full_name'] ?? '') ?>"
                       placeholder="Иван Иванов"
                       required>
            </div>
            <div class="form-group">
                <label for="username">Потребителско ime</label>
                <input type="text"
                       id="username"
                       name="username"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       placeholder="ivan_dj"
                       required
                       minlength="3">
            </div>
            <div class="form-group">
                <label for="email">Имейл адрес</label>
                <input type="email"
                       id="email"
                       name="email"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="ivan@example.com"
                       required>
            </div>
            <div class="form-group">
                <label for="password">Парола</label>
                <input type="password"
                       id="password"
                       name="password"
                       placeholder="Минимум 6 символа"
                       required
                       minlength="6">
            </div>
            <div class="form-group">
                <label for="password2">Потвърди паролата</label>
                <input type="password"
                       id="password2"
                       name="password2"
                       placeholder="Повтори паролата"
                       required
                       minlength="6">
            </div>
            <button type="submit" class="btn btn--primary btn--full">
                Създай акаунт
            </button>
        </form>

        <p class="form-link">
            Вече имаш акаунт?
            <a href="<?= SITE_URL ?>/pages/login.php">Влез тук</a>
        </p>
    </div>
</main>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>