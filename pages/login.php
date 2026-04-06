<?php
// pages/login.php
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
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    $result   = loginUser($email, $password);

    if ($result['success']) {
        $redirect = $_SESSION['redirect_after_login'] ?? SITE_URL;
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirect);
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
    <title>Вход — <?= SITE_NAME ?></title>
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
        <h1 class="form-box__title">ВХОД</h1>
        <p class="form-box__sub">Влез в своя акаунт</p>

        <?php showFlash(); ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Имейл адрес</label>
                <input type="email"
                       id="email"
                       name="email"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="ivan@example.com"
                       required
                       autofocus>
            </div>
            <div class="form-group">
                <label for="password">Парола</label>
                <input type="password"
                       id="password"
                       name="password"
                       placeholder="Твоята парола"
                       required>
            </div>
            <button type="submit" class="btn btn--primary btn--full">
                Влез
            </button>
        </form>

        <p class="form-link">
            Нямаш акаунт?
            <a href="<?= SITE_URL ?>/pages/register.php">Регистрирай се</a>
        </p>
    </div>
</main>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>