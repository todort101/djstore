<?php
// admin/products_add.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
requireAdmin();

$db         = getDB();
$categories = getAllCategories();
$error      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']         ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $price       = (float)($_POST['price']     ?? 0);
    $stock       = (int)($_POST['stock']       ?? 0);
    $brand       = trim($_POST['brand']        ?? '');
    $description = trim($_POST['description']  ?? '');
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        $error = 'Моля въведи наименование на продукта.';
    } elseif (!$category_id) {
        $error = 'Моля избери категория.';
    } elseif ($price <= 0) {
        $error = 'Моля въведи валидна цена.';
    } else {
        // Генерирай slug
        $slug  = createSlug($name);
        $check = $db->prepare("SELECT id FROM products WHERE slug = ?");
        $check->bind_param('s', $slug);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $slug = $slug . '-' . time();
        }

        // Качване на снимка
        $imageName = null;
        if (!empty($_FILES['image']['name'])) {
            $allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif');
            $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Позволени формати: JPG, PNG, WEBP, GIF.';
            } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $error = 'Снимката не трябва да е по-голяма от 5MB.';
            } else {
                $imageName = $slug . '.' . $ext;
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $imageName);
            }
        }

        if (!$error) {
            $stmt = $db->prepare(
                "INSERT INTO products
                 (category_id, name, slug, description, price, stock, brand, image, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                'isssdissi',
                $category_id, $name, $slug, $description,
                $price, $stock, $brand, $imageName, $is_active
            );
            $stmt->execute();
            $successMsg = 'Продуктът ' . $name . ' е добавен успешно!';
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
    <title>Добави продукт — Админ — <?= SITE_NAME ?></title>
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
            Добави продукт
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
                           value="<?= e($_POST['name'] ?? '') ?>"
                           placeholder="Pioneer DDJ-FLX6"
                           required>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label for="prod_cat">Категория</label>
                        <select id="prod_cat" name="category_id" required>
                            <option value="">-- Избери --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"
                                <?php if (($_POST['category_id'] ?? '') == $cat['id']) echo 'selected'; ?>>
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
                               value="<?= e($_POST['brand'] ?? '') ?>"
                               placeholder="Pioneer DJ">
                    </div>
                </div>

                <div class="form-group">
                    <label for="prod_desc">Описание</label>
                    <textarea id="prod_desc"
                              name="description"
                              rows="4"
                              placeholder="Подробно описание на продукта..."><?= e($_POST['description'] ?? '') ?></textarea>
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
                               value="<?= e($_POST['price'] ?? '') ?>"
                               placeholder="499.99"
                               required>
                    </div>
                    <div class="form-group">
                        <label for="prod_stock">Наличност (бр.)</label>
                        <input type="number"
                               id="prod_stock"
                               name="stock"
                               min="0"
                               value="<?= e($_POST['stock'] ?? '0') ?>"
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               <?php if (!isset($_POST['name']) || isset($_POST['is_active'])) echo 'checked'; ?>
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
                    Снимка на продукта
                </h2>
                <div class="form-group">
                    <label>Качи снимка (JPG, PNG, WEBP — макс. 5MB)</label>
                    <input type="file"
                           name="image"
                           accept="image/*"
                           style="background:var(--dark-3);border:1px solid var(--dark-4);
                                  border-radius:4px;padding:10px;color:var(--white);width:100%;">
                </div>
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn btn--primary">
                    Добави продукта
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