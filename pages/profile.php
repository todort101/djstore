<?php
// pages/profile.php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/cart.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();

$user  = getCurrentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db        = getDB();
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone']     ?? '');
    $address   = trim($_POST['address']   ?? '');
    $newPass   = $_POST['new_password']   ?? '';
    $newPass2  = $_POST['new_password2']  ?? '';

    if (empty($full_name)) {
        $error = 'Пълното ime не може да е празно.';
    } elseif ($newPass && strlen($newPass) < 6) {
        $error = 'Новата парола трябва да е поне 6 символа.';
    } elseif ($newPass && $newPass !== $newPass2) {
        $error = 'Новите пароли не съвпадат.';
    } else {
        if ($newPass) {
            $hash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmt = $db->prepare(
                "UPDATE users SET full_name=?, phone=?, address=?, password=? WHERE id=?"
            );
            $stmt->bind_param('ssssi', $full_name, $phone, $address, $hash, $user['id']);
        } else {
            $stmt = $db->prepare(
                "UPDATE users SET full_name=?, phone=?, address=? WHERE id=?"
            );
            $stmt->bind_param('sssi', $full_name, $phone, $address, $user['id']);
        }
        $stmt->execute();

        $_SESSION['full_name'] = $full_name;
        setFlash('success', 'Профилът е обновен успешно!');
        header('Location: ' . SITE_URL . '/pages/profile.php');
        exit;
    }
}

$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профил — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include '../includes/header.php'; ?>

<main class="page-section">
    <div class="container" style="max-width:680px">
        

        <?php showFlash(); ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Акаунт инфо -->
        <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                    border-radius:var(--radius-lg);padding:24px;margin-bottom:24px;">
            <div style="display:flex;gap:16px;align-items:center;">
                <div style="width:64px;height:64px;background:var(--accent);
                            border-radius:50%;display:flex;align-items:center;
                            justify-content:center;font-size:1.8rem;color:var(--black);
                            font-family:var(--font-display);">
                    <?= mb_strtoupper(mb_substr($user['username'], 0, 1)) ?>
                </div>
                <div>
                    <p style="font-family:var(--font-display);font-size:1.5rem;
                               color:var(--white);">
                        <?= e($user['full_name']) ?>
                    </p>
                    <p style="color:var(--grey);font-size:.9rem;">
                        <?= e($user['email']) ?> ·
                        <span style="color:<?= $user['role']==='admin' ? 'var(--accent)' : 'var(--grey)' ?>">
                            <?= $user['role'] === 'admin' ? '⚙ Администратор' : '👤 Потребител' ?>
                        </span>
                    </p>
                    <p style="color:var(--grey);font-size:.8rem;margin-top:4px;">
                        Регистриран на <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Форма за редакция -->
        <form method="POST"
              style="background:var(--dark-2);border:1px solid var(--dark-3);
                     border-radius:var(--radius-lg);padding:32px;">
            <h2 style="font-size:1.5rem;color:var(--white);margin-bottom:24px;
                       padding-bottom:16px;border-bottom:1px solid var(--dark-4);">
                Редактирай данните
            </h2>

            <div class="form-group">
                <label>Потребителско ime</label>
                <input type="text" value="<?= e($user['username']) ?>" disabled
                       style="opacity:.5;cursor:not-allowed;">
            </div>
            <div class="form-group">
                <label>Имейл адрес</label>
                <input type="email" value="<?= e($user['email']) ?>" disabled
                       style="opacity:.5;cursor:not-allowed;">
            </div>
            <div class="form-group">
                <label for="full_name">Пълно ime *</label>
                <input type="text" id="full_name" name="full_name"
                       value="<?= e($_POST['full_name'] ?? $user['full_name']) ?>"
                       required>
            </div>
            <div class="form-group">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone"
                       value="<?= e($_POST['phone'] ?? $user['phone'] ?? '') ?>"
                       placeholder="0888 123 456">
            </div>
            <div class="form-group">
                <label for="address">Адрес за доставка</label>
                <textarea id="address" name="address"
                          rows="3"
                          placeholder="гр. София, ул. Примерна 1"><?= e($_POST['address'] ?? $user['address'] ?? '') ?></textarea>
            </div>

            <div style="border-top:1px solid var(--dark-4);padding-top:24px;margin-top:8px;">
                <h3 style="font-size:1.2rem;color:var(--white);margin-bottom:16px;">
                    Смяна на парола
                    <small style="color:var(--grey);font-size:.8rem;"> (остави празно за без промяна)</small>
                </h3>
                <div class="form-group">
                    <label for="new_password">Нова парола</label>
                    <input type="password" id="new_password" name="new_password"
                           placeholder="Минимум 6 символа">
                </div>
                <div class="form-group">
                    <label for="new_password2">Потвърди новата парола</label>
                    <input type="password" id="new_password2" name="new_password2"
                           placeholder="Повтори новата парола">
                </div>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="btn btn--primary">
                    💾 Запази промените
                </button>
                <a href="<?= SITE_URL ?>/pages/orders.php" class="btn btn--ghost">
                    📦 Моите поръчки
                </a>
            </div>
        </form>

    </div>
</main>

<?php include '../includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>