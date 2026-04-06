<?php
// admin/promo_codes.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
requireAdmin();

$db    = getDB();
$error = '';

// ── Изтриване ─────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id   = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM promo_codes WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    setFlash('success', 'Промо кодът е изтрит.');
    header('Location: ' . SITE_URL . '/admin/promo_codes.php');
    exit;
}

// ── Превключи активен/неактивен ───────────────────────────────
if (isset($_GET['toggle'])) {
    $id   = (int)$_GET['toggle'];
    $stmt = $db->prepare(
        "UPDATE promo_codes SET is_active = IF(is_active=1,0,1) WHERE id=?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: ' . SITE_URL . '/admin/promo_codes.php');
    exit;
}

// ── Зареди за редакция ────────────────────────────────────────
$editPromo = null;
if (isset($_GET['edit'])) {
    $editPromo = getPromoById((int)$_GET['edit']);
}

// ── Добавяне / Редакция ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code           = strtoupper(trim($_POST['code']           ?? ''));
    $type           = $_POST['type']                           ?? 'percent';
    $value          = (float)($_POST['value']                  ?? 0);
    $min_order      = (float)($_POST['min_order']              ?? 0);
    $max_uses       = $_POST['max_uses']       !== '' ? (int)$_POST['max_uses']       : null;
    $per_user_limit = $_POST['per_user_limit'] !== '' ? (int)$_POST['per_user_limit'] : null;
    $expires        = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $is_active      = isset($_POST['is_active']) ? 1 : 0;
    $promoId        = (int)($_POST['promo_id'] ?? 0);

    if (empty($code)) {
        $error = 'Моля въведи код.';
    } elseif (!in_array($type, ['percent', 'fixed'])) {
        $error = 'Невалиден тип отстъпка.';
    } elseif ($value <= 0) {
        $error = 'Стойността трябва да е по-голяма от 0.';
    } elseif ($type === 'percent' && $value > 100) {
        $error = 'Процентът не може да е повече от 100.';
    } else {
        if ($promoId) {
            // Редакция
            $stmt = $db->prepare(
                "UPDATE promo_codes SET
                    code=?, type=?, value=?, min_order=?,
                    max_uses=?, per_user_limit=?, expires_at=?, is_active=?
                 WHERE id=?"
            );
            $stmt->bind_param(
                'ssddiisii',
                $code, $type, $value, $min_order,
                $max_uses, $per_user_limit, $expires, $is_active, $promoId
            );
            $stmt->execute();
            setFlash('success', 'Промо кодът е обновен.');
        } else {
            // Провери за дублиране
            $check = $db->prepare("SELECT id FROM promo_codes WHERE code=?");
            $check->bind_param('s', $code);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = 'Промо код ' . $code . ' вече съществува.';
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO promo_codes
                     (code, type, value, min_order, max_uses, per_user_limit, expires_at, is_active)
                     VALUES (?,?,?,?,?,?,?,?)"
                );
                $stmt->bind_param(
                    'ssddiisi',
                    $code, $type, $value, $min_order,
                    $max_uses, $per_user_limit, $expires, $is_active
                );
                $stmt->execute();
                setFlash('success', 'Промо код ' . $code . ' е създаден.');
            }
        }

        if (!$error) {
            header('Location: ' . SITE_URL . '/admin/promo_codes.php');
            exit;
        }
    }
}

$promoCodes = getAllPromoCodes();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Промо кодове — Админ — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        html, body { background: #0a0a0a !important; color: #f5f5f0 !important; min-height: 100vh; }
        .promo-type-percent { color: #4af; }
        .promo-type-fixed   { color: var(--accent); }
        code {
            background: var(--dark-3);
            border: 1px solid var(--dark-4);
            border-radius: 3px;
            padding: 2px 8px;
            font-size: .85rem;
            letter-spacing: .1em;
            color: var(--accent);
        }
    </style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<div class="admin-layout">
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <h1 class="admin-title">🏷️ Промо кодове</h1>

        <?php include 'includes/admin_flash.php'; ?>

        <div style="display:grid;grid-template-columns:380px 1fr;gap:32px;align-items:start;">

            <!-- ── ФОРМА ──────────────────────────────── -->
            <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                        border-radius:var(--radius-lg);padding:28px;
                        position:sticky;top:calc(var(--header-h) + 16px);">
                <h2 style="font-size:1.4rem;color:var(--white);margin-bottom:20px;
                           padding-bottom:14px;border-bottom:1px solid var(--dark-4);">
                    <?= $editPromo ? 'Редактирай код' : 'Нов промо код' ?>
                </h2>

                <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?php if ($editPromo): ?>
                    <input type="hidden" name="promo_id" value="<?= $editPromo['id'] ?>">
                    <?php endif; ?>

                    <!-- Код -->
                    <div class="form-group">
                        <label>Код</label>
                        <input type="text"
                               name="code"
                               value="<?= e($_POST['code'] ?? $editPromo['code'] ?? '') ?>"
                               placeholder="DJSTORE10"
                               maxlength="50"
                               style="text-transform:uppercase;letter-spacing:.1em;font-weight:700;"
                               required>
                        <small style="color:var(--grey);font-size:.78rem;">
                            Само главни букви, цифри и тире
                        </small>
                    </div>

                    <!-- Тип -->
                    <div class="form-group">
                        <label>Тип отстъпка</label>
                        <select name="type" id="promoType" onchange="toggleType()">
                            <option value="percent"
                                <?= (($_POST['type'] ?? $editPromo['type'] ?? '') === 'percent') ? 'selected' : '' ?>>
                                % Процент
                            </option>
                            <option value="fixed"
                                <?= (($_POST['type'] ?? $editPromo['type'] ?? '') === 'fixed') ? 'selected' : '' ?>>
                                € Фиксирана сума
                            </option>
                        </select>
                    </div>

                    <!-- Стойност -->
                    <div class="form-group">
                        <label id="valueLabel">Стойност (%)</label>
                        <input type="number"
                               name="value"
                               id="promoValue"
                               value="<?= e($_POST['value'] ?? $editPromo['value'] ?? '') ?>"
                               step="0.01"
                               min="0.01"
                               placeholder="10"
                               required>
                    </div>

                    <!-- Минимална поръчка -->
                    <div class="form-group">
                        <label>Минимална поръчка (€)</label>
                        <input type="number"
                               name="min_order"
                               value="<?= e($_POST['min_order'] ?? $editPromo['min_order'] ?? '0') ?>"
                               step="0.01"
                               min="0"
                               placeholder="0">
                        <small style="color:var(--grey);font-size:.78rem;">
                            0 = без минимум
                        </small>
                    </div>

                    <!-- Максимален брой употреби -->
                    <div class="form-group">
                        <label>Макс. употреби (общо)</label>
                        <input type="number"
                               name="max_uses"
                               value="<?= e($_POST['max_uses'] ?? $editPromo['max_uses'] ?? '') ?>"
                               min="1"
                               placeholder="Неограничено">
                        <small style="color:var(--grey);font-size:.78rem;">
                            Оставете празно за неограничено
                        </small>
                    </div>

                    <!-- Лимит на акаунт -->
                    <div class="form-group">
                        <label>Лимит на акаунт</label>
                        <input type="number"
                               name="per_user_limit"
                               value="<?= e($_POST['per_user_limit'] ?? $editPromo['per_user_limit'] ?? '') ?>"
                               min="1"
                               placeholder="Неограничено">
                        <small style="color:var(--grey);font-size:.78rem;">
                            Колко пъти може един акаунт да използва кода. Оставете празно за без лимит.
                        </small>
                    </div>

                    <!-- Изтича на -->
                    <div class="form-group">
                        <label>Изтича на</label>
                        <input type="date"
                               name="expires_at"
                               value="<?= e($_POST['expires_at'] ?? $editPromo['expires_at'] ?? '') ?>"
                               min="<?= date('Y-m-d') ?>">
                        <small style="color:var(--grey);font-size:.78rem;">
                            Оставете празно за без краен срок
                        </small>
                    </div>

                    <!-- Активен -->
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   <?php
                                   $isActive = $_POST['is_active'] ?? $editPromo['is_active'] ?? 1;
                                   if ($isActive) echo 'checked';
                                   ?>
                                   style="width:18px;height:18px;accent-color:var(--accent)">
                            <span>Активен</span>
                        </label>
                    </div>

                    <div style="display:flex;gap:10px;">
                        <button type="submit" class="btn btn--primary">
                            <?= $editPromo ? '💾 Запази' : '➕ Създай код' ?>
                        </button>
                        <?php if ($editPromo): ?>
                        <a href="<?= SITE_URL ?>/admin/promo_codes.php"
                           class="btn btn--ghost">Отказ</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- ── ТАБЛИЦА ─────────────────────────────── -->
            <div>
                <?php if (empty($promoCodes)): ?>
                <div class="empty-state" style="padding:40px 0;">
                    <div class="empty-state__icon">🏷️</div>
                    <h2 class="empty-state__title">Няма промо кодове</h2>
                    <p>Създай първия промо код от формата вляво.</p>
                </div>
                <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Код</th>
                            <th>Тип</th>
                            <th>Стойност</th>
                            <th>Мин. поръчка</th>
                            <th>Употреби</th>
                            <th>Лимит/акаунт</th>
                            <th>Изтича</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promoCodes as $p): ?>
                        <tr>
                            <td><code><?= e($p['code']) ?></code></td>
                            <td>
                                <?php if ($p['type'] === 'percent'): ?>
                                <span class="promo-type-percent">% Процент</span>
                                <?php else: ?>
                                <span class="promo-type-fixed">€ Фиксирана</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--white);font-weight:700;">
                                <?php if ($p['type'] === 'percent'): ?>
                                    <?= $p['value'] ?>%
                                <?php else: ?>
                                    <?= formatPrice($p['value']) ?>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--grey-light);">
                                <?= $p['min_order'] > 0 ? formatPrice($p['min_order']) : '—' ?>
                            </td>
                            <td>
                                <span style="color:var(--white)"><?= $p['used_count'] ?></span>
                                <?php if ($p['max_uses']): ?>
                                <span style="color:var(--grey)"> / <?= $p['max_uses'] ?></span>
                                <?php else: ?>
                                <span style="color:var(--grey)"> / ∞</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['per_user_limit']): ?>
                                <span style="color:var(--warning);">
                                    <?= $p['per_user_limit'] ?> × акаунт
                                </span>
                                <?php else: ?>
                                <span style="color:var(--grey);">Неограничено</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--grey-light);">
                                <?php if ($p['expires_at']): ?>
                                    <?= date('d.m.Y', strtotime($p['expires_at'])) ?>
                                <?php else: ?>
                                    <span style="color:var(--grey)">Без срок</span>
                                <?php endif; ?>
                            </td>
                            <td><?= promoStatus($p) ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="?edit=<?= $p['id'] ?>"
                                       class="btn-edit">✏</a>
                                    <a href="?toggle=<?= $p['id'] ?>"
                                       class="btn-edit"
                                       style="<?= $p['is_active'] ? 'color:#ffaa00;border-color:rgba(255,170,0,.3)' : '' ?>"
                                       title="<?= $p['is_active'] ? 'Деактивирай' : 'Активирай' ?>">
                                        <?= $p['is_active'] ? '⏸' : '▶' ?>
                                    </a>
                                    <a href="?delete=<?= $p['id'] ?>"
                                       class="btn-delete"
                                       data-confirm="Изтрий промо код <?= e($p['code']) ?>?">
                                        🗑
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
function toggleType() {
    const type  = document.getElementById('promoType').value;
    const label = document.getElementById('valueLabel');
    const input = document.getElementById('promoValue');
    if (type === 'percent') {
        label.textContent = 'Стойност (%)';
        input.max         = '100';
        input.placeholder = '10';
    } else {
        label.textContent = 'Стойност (€)';
        input.removeAttribute('max');
        input.placeholder = '15.00';
    }
}
toggleType();

// Auto uppercase на кода
document.querySelector('input[name="code"]').addEventListener('input', function() {
    const pos  = this.selectionStart;
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
    this.setSelectionRange(pos, pos);
});
</script>
</body>
</html>