<?php
// admin/products.php
$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
requireAdmin();

$db = getDB();

// ── Изтриване ──────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    $stmt = $db->prepare("UPDATE products SET is_active=0 WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    setFlash('success', 'Продуктът е деактивиран успешно.');
    header('Location: ' . SITE_URL . '/admin/products.php');
    exit;
}

// ── Търсене ────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$sql = "SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id";
if ($search) {
    $s    = '%' . $db->real_escape_string($search) . '%';
    $sql .= " WHERE p.name LIKE '$s' OR p.brand LIKE '$s'";
}
$sql .= " ORDER BY p.id DESC";
$products = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Продукти — Админ — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>html,body{background:#0a0a0a!important;color:#f5f5f0!important;min-height:100vh}</style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<div class="admin-layout">
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <h1 class="admin-title">
            🎛️ Продукти
            <a href="<?= SITE_URL ?>/admin/products_add.php"
               class="btn btn--primary btn--sm">+ Добави продукт</a>
        </h1>

        <?php include 'includes/admin_flash.php'; ?>

        <!-- Търсене -->
        <form method="GET" style="margin-bottom:24px;display:flex;gap:12px;">
            <input type="text" name="search" value="<?= e($search) ?>"
                   placeholder="Търси по ime или марка..."
                   style="flex:1;background:var(--dark-2);border:1px solid var(--dark-4);
                          border-radius:4px;padding:10px 16px;color:var(--white);">
            <button type="submit" class="btn btn--ghost">Търси</button>
            <?php if ($search): ?>
            <a href="<?= SITE_URL ?>/admin/products.php" class="btn btn--ghost">✕ Изчисти</a>
            <?php endif; ?>
        </form>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Снимка</th>
                    <th>Продукт</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Наличност</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;color:var(--grey);padding:40px;">
                        Няма намерени продукти.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td style="color:var(--grey)"><?= $p['id'] ?></td>
                    <td>
                        <img src="<?= productImage($p['image']) ?>"
                             alt="<?= e($p['name']) ?>">
                    </td>
                    <td>
                        <strong style="color:var(--white)"><?= e($p['name']) ?></strong><br>
                        <small style="color:var(--grey)"><?= e($p['brand'] ?? '') ?></small>
                    </td>
                    <td style="color:var(--grey-light)"><?= e($p['category_name']) ?></td>
                    <td class="cart-price"><?= formatPrice($p['price']) ?></td>
                    <td>
                        <?php if ($p['stock'] > 0): ?>
                        <span style="color:var(--success)"><?= $p['stock'] ?> бр.</span>
                        <?php else: ?>
                        <span style="color:var(--danger)">Изчерпан</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['is_active']): ?>
                        <span class="status-badge status-delivered">Активен</span>
                        <?php else: ?>
                        <span class="status-badge status-cancelled">Неактивен</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="table-actions">
                            <a href="<?= SITE_URL ?>/admin/products_edit.php?id=<?= $p['id'] ?>"
                               class="btn-edit">✏ Редакция</a>
                            <a href="?delete=<?= $p['id'] ?>"
                               class="btn-delete"
                               data-confirm="Деактивирай продукт «<?= e($p['name']) ?>»?">
                               🗑 Изтрий
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>