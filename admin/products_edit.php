<?php
// admin/products_edit.php
$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
requireAdmin();

$db         = getDB();
$categories = getAllCategories();
$id         = (int)($_GET['id'] ?? 0);
$product    = getProductById($id);

if (!$product) {
    setFlash('error', 'Продуктът не е намерен.');
    header('Location: ' . SITE_URL . '/admin/products.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']         ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price       = (float)($_POST['price']     ?? 0);
    $stock       = (int)($_POST['stock']       ?? 0);
    $brand       = trim($_POST['brand']        ?? '');
    $description = trim($_POST['description']  ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Наименованието не може да е празно.';
    } elseif (!$category_id) {
        $error = 'Моля избери категория.';
    } elseif ($price <= 0) {
        $error = 'Моля въведи валидна цена.';
    } else {
        // Качване на нова снимка
        $imageName = $product['image'];
        if (!empty($_FILES['image']['name'])) {
            $allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif');
            $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Позволени формати: JPG, PNG, WEBP, GIF.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'Снимката не трябва да е по-голяма от 5MB.';
            } else {
                // Изтрий старата снимка
                if ($imageName && file_exists(UPLOAD_DIR . $imageName)) {
                    unlink(UPLOAD_DIR . $imageName);
                }
                $slug      = createSlug($name);
                $imageName = $slug . '-' . time() . '.' . $ext;
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $imageName);
            }
        }

        if (!$error) {
            $stmt = $db->prepare(
    "UPDATE products SET
        category_id=?, name=?, description=?, price=?,
        stock=?, brand=?, image=?, is_active=?
     WHERE id=?"
);
$stmt->bind_param(
    'issdissii',
    $category_id,
    $name,
    $description,
    $price,
    $stock,
    $brand,
    $imageName,
    $is_active,
    $id
);
            $stmt->execute();
            $successMsg = 'Продуктът ' . $name . ' е обновен успешно!';
            setFlash('success', $successMsg);
            header('Location: ' . SITE_URL . '/admin/products.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирай продукт — Админ — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        html, body { background: #0a0a0a !important; color: #f5f5f0 !important; min-height: 100vh; }
    </style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<div class="admin-layout">
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <h1 class="admin-title">
            Редактирай продукт
            <a href="<?= SITE_URL ?>/admin/products.php"
               class="btn btn--ghost btn--sm">Назад</a>
        </h1>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" style="max-width:720px;">

            <!-- Основна информация -->
            <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                        border-radius:var(--radius-lg);padding:32px;margin-bottom:24px;">
                <h2 style="font-size:1.5rem;color:var(--white);margin-bottom:24px;
                           padding-bottom:16px;border-bottom:1px solid var(--dark-4);">
                    Основна информация
                </h2>

                <div class="form-group">
                    <label for="prod_name">Наименование</label>
                    <input type="text"
                           id="prod_name"
                           name="name"
                           value="<?= e($_POST['name'] ?? $product['name']) ?>"
                           required>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label for="prod_cat">Категория</label>
                        <select id="prod_cat" name="category_id" required>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"
                                <?php
                                $selVal = $_POST['category_id'] ?? $product['category_id'];
                                if ($selVal == $cat['id']) echo 'selected';
                                ?>>
                                <?= e($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="prod_brand">Марка</label>
                        <input type="text"
                               id="prod_brand"
                               name="brand"
                               value="<?= e($_POST['brand'] ?? $product['brand'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="prod_desc">Описание</label>
                    <textarea id="prod_desc"
                              name="description"
                              rows="4"><?= e($_POST['description'] ?? $product['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Цена и наличност -->
            <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                        border-radius:var(--radius-lg);padding:32px;margin-bottom:24px;">
                <h2 style="font-size:1.5rem;color:var(--white);margin-bottom:24px;
                           padding-bottom:16px;border-bottom:1px solid var(--dark-4);">
                    Цена и наличност
                </h2>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label for="prod_price">Цена (EUR)</label>
                        <input type="number"
                               id="prod_price"
                               name="price"
                               step="0.01"
                               min="0"
                               value="<?= e($_POST['price'] ?? $product['price']) ?>"
                               required>
                    </div>
                    <div class="form-group">
                        <label for="prod_stock">Наличност (бр.)</label>
                        <input type="number"
                               id="prod_stock"
                               name="stock"
                               min="0"
                               value="<?= e($_POST['stock'] ?? $product['stock']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               <?php
                               $isActive = $_POST['is_active'] ?? $product['is_active'];
                               if ($isActive) echo 'checked';
                               ?>
                               style="width:18px;height:18px;accent-color:var(--accent)">
                        <span>Активен (видим в магазина)</span>
                    </label>
                </div>
            </div>

            <!-- Снимка -->
            <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                        border-radius:var(--radius-lg);padding:32px;margin-bottom:24px;">
                <h2 style="font-size:1.5rem;color:var(--white);margin-bottom:24px;
                           padding-bottom:16px;border-bottom:1px solid var(--dark-4);">
                    Снимка
                </h2>

                <?php if ($product['image']): ?>
                <div style="margin-bottom:16px;">
                    <p style="color:var(--grey);font-size:.85rem;margin-bottom:8px;">
                        Текуща снимка:
                    </p>
                    <img src="<?= productImage($product['image']) ?>"
                         alt="current"
                         style="max-width:160px;background:var(--dark-3);
                                border-radius:var(--radius);padding:8px;">
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Качи нова снимка (оставете празно за без промяна)</label>
                    <input type="file"
                           name="image"
                           accept="image/*"
                           style="background:var(--dark-3);border:1px solid var(--dark-4);
                                  border-radius:4px;padding:10px;color:var(--white);width:100%;">
                </div>
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn--primary">
                    Запази промените
                </button>
                <a href="<?= SITE_URL ?>/admin/products.php" class="btn btn--ghost">
                    Отказ
                </a>
            </div>

        </form>
    </main>
</div>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>