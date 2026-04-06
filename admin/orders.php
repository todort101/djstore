<?php
// admin/orders.php
$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
requireAdmin();

$db = getDB();

// ── Смяна на статус ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $oid    = (int)$_POST['order_id'];
    $status = $_POST['status'] ?? '';
    $allowed = ['pending','processing','shipped','delivered','cancelled'];
    if (in_array($status, $allowed)) {
        $stmt = $db->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param('si', $status, $oid);
        $stmt->execute();
        setFlash('success', "Статусът на поръчка #{$oid} е обновен.");
    }
    header('Location: ' . SITE_URL . '/admin/orders.php' . (isset($_GET['id']) ? '?id='.(int)$_GET['id'] : ''));
    exit;
}

// ── Детайли на поръчка ─────────────────────────────────────────
$viewOrder = null;
$viewItems = [];
if (!empty($_GET['id'])) {
    $oid  = (int)$_GET['id'];
    $stmt = $db->prepare(
        "SELECT o.*, u.username, u.email, u.full_name AS user_full_name
         FROM orders o JOIN users u ON u.id = o.user_id
         WHERE o.id = ?"
    );
    $stmt->bind_param('i', $oid);
    $stmt->execute();
    $viewOrder = $stmt->get_result()->fetch_assoc();
    if ($viewOrder) $viewItems = getOrderItems($oid);
}

// ── Списък с поръчки ──────────────────────────────────────────
$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT o.*, u.username, u.full_name
        FROM orders o JOIN users u ON u.id = o.user_id";
if ($statusFilter) {
    $sf  = $db->real_escape_string($statusFilter);
    $sql .= " WHERE o.status = '$sf'";
}
$sql .= " ORDER BY o.created_at DESC";
$orders = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поръчки — Админ</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>html,body{background:#0a0a0a!important;color:#f5f5f0!important;min-height:100vh}</style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<div class="admin-layout">
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="admin-content">

        <?php if ($viewOrder): ?>
        <!-- ── ДЕТАЙЛИ ─────────────────────────────────────── -->
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:32px;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn--ghost btn--sm">← Назад</a>
            <h1 class="admin-title" style="margin-bottom:0">
                Поръчка #<?= $viewOrder['id'] ?>
            </h1>
        </div>

        <?php include 'includes/admin_flash.php'; ?>

        <div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

            <!-- Артикули -->
            <div>
                <table class="admin-table" style="margin-bottom:0">
                    <thead>
                        <tr>
                            <th>Продукт</th>
                            <th>Ед. цена</th>
                            <th>Бр.</th>
                            <th>Сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viewItems as $item): ?>
                        <tr>
                            <td style="color:var(--white)"><?= e($item['product_name']) ?></td>
                            <td class="cart-price"><?= formatPrice($item['price']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td class="cart-price">
                                <?= formatPrice($item['price'] * $item['quantity']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Детайли + Статус -->
            <div style="display:flex;flex-direction:column;gap:16px;">

                <!-- Инфо за клиент -->
                <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                            border-radius:var(--radius-lg);padding:24px;">
                    <h3 style="font-size:1.2rem;color:var(--white);margin-bottom:16px;">
                        👤 Клиент
                    </h3>
                    <p style="color:var(--grey-light);margin-bottom:4px;">
                        <?= e($viewOrder['user_full_name']) ?>
                    </p>
                    <p style="color:var(--grey);font-size:.85rem;">
                        <?= e($viewOrder['username']) ?> · <?= e($viewOrder['email']) ?>
                    </p>
                </div>

                <!-- Доставка -->
                <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                            border-radius:var(--radius-lg);padding:24px;">
                    <h3 style="font-size:1.2rem;color:var(--white);margin-bottom:16px;">
                        📦 Доставка
                    </h3>
                    <p style="color:var(--grey-light);margin-bottom:4px;">
                        <?= e($viewOrder['shipping_name']) ?>
                    </p>
                    <p style="color:var(--grey);font-size:.85rem;margin-bottom:4px;">
                        📞 <?= e($viewOrder['shipping_phone']) ?>
                    </p>
                    <p style="color:var(--grey);font-size:.85rem;">
                        📍 <?= e($viewOrder['shipping_address']) ?>
                    </p>
                    <?php if ($viewOrder['notes']): ?>
                    <p style="color:var(--warning);font-size:.85rem;margin-top:8px;
                               padding-top:8px;border-top:1px solid var(--dark-4);">
                        💬 <?= e($viewOrder['notes']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Обобщение -->
                <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                            border-radius:var(--radius-lg);padding:24px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <span style="color:var(--grey)">Дата</span>
                        <span style="color:var(--white)">
                            <?= date('d.m.Y H:i', strtotime($viewOrder['created_at'])) ?>
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:12px 0;
                                border-top:1px solid var(--dark-4);margin-top:8px;">
                        <span style="font-family:var(--font-condensed);font-weight:700;
                                     color:var(--accent);font-size:1.1rem;">ОБЩО</span>
                        <span style="font-family:var(--font-condensed);font-weight:700;
                                     color:var(--accent);font-size:1.1rem;">
                            <?= formatPrice($viewOrder['total_amount']) ?>
                        </span>
                    </div>
                </div>

                <!-- Смяна на статус -->
                <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                            border-radius:var(--radius-lg);padding:24px;">
                    <h3 style="font-size:1.2rem;color:var(--white);margin-bottom:16px;">
                        🔄 Статус
                    </h3>
                    <div style="margin-bottom:12px;">
                        <span class="status-badge status-<?= $viewOrder['status'] ?>">
                            <?= getOrderStatus($viewOrder['status']) ?>
                        </span>
                    </div>
                    <form method="POST" style="display:flex;gap:8px;">
                        <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                        <input type="hidden" name="" value="">
                        <select name="status"
                                style="flex:1;background:var(--dark-3);border:1px solid var(--dark-4);
                                       border-radius:4px;padding:8px;color:var(--white);">
                            <?php
                            $statuses = ['pending','processing','shipped','delivered','cancelled'];
                            foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>"
                                <?= $viewOrder['status'] === $s ? 'selected' : '' ?>>
                                <?= getOrderStatus($s) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn--primary btn--sm">
                            Запази
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <?php else: ?>
        <!-- ── СПИСЪК ──────────────────────────────────────── -->
        <h1 class="admin-title">📦 Поръчки</h1>

        <?php include 'includes/admin_flash.php'; ?>

        <!-- Филтър по статус -->
        <div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/admin/orders.php"
               class="btn btn--sm <?= !$statusFilter ? 'btn--primary' : 'btn--ghost' ?>">
                Всички
            </a>
            <?php
            $statuses = ['pending','processing','shipped','delivered','cancelled'];
            foreach ($statuses as $s): ?>
            <a href="?status=<?= $s ?>"
               class="btn btn--sm <?= $statusFilter === $s ? 'btn--primary' : 'btn--ghost' ?>">
                <?= getOrderStatus($s) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Клиент</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Сума</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;color:var(--grey);padding:40px;">
                        Няма поръчки.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td>
                        <span style="color:var(--white)"><?= e($o['full_name']) ?></span><br>
                        <small style="color:var(--grey)"><?= e($o['username']) ?></small>
                    </td>
                    <td style="color:var(--grey-light)">
                        <?= date('d.m.Y H:i', strtotime($o['created_at'])) ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?= $o['status'] ?>">
                            <?= getOrderStatus($o['status']) ?>
                        </span>
                    </td>
                    <td class="cart-price"><?= formatPrice($o['total_amount']) ?></td>
                    <td>
                        <a href="?id=<?= $o['id'] ?>" class="btn-edit">Виж →</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </main>
</div>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>