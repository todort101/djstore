<?php
// admin/categories.php
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
    $id = (int)$_GET['delete'];
    // Проверяваме за ВСИЧКИ продукти в категорията
    $check = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id=?");
    $check->bind_param('i', $id);
    $check->execute();
    $count = (int)$check->get_result()->fetch_row()[0];

    if ($count > 0) {
        $msg = 'Не можеш да изтриеш категория с ' . $count . ' активни продукта.';
        setFlash('error', $msg);
    } else {
        $stmt = $db->prepare("DELETE FROM categories WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        setFlash('success', 'Категорията е изтрита.');
    }
    header('Location: ' . SITE_URL . '/admin/categories.php');
    exit;
}

// ── Зареди за редакция ────────────────────────────────────────
$editCat = null;
if (isset($_GET['edit'])) {
    $eid  = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->bind_param('i', $eid);
    $stmt->execute();
    $editCat = $stmt->get_result()->fetch_assoc();
}

// ── Добавяне / Редакция ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon        = trim($_POST['icon']        ?? '🎛️');
    $catId       = (int)($_POST['cat_id']     ?? 0);

    if (empty($name)) {
        $error = 'Наименованието не може да е празно.';
    } else {
        $slug = createSlug($name);

        if ($catId) {
            $stmt = $db->prepare(
                "UPDATE categories SET name=?, slug=?, description=?, icon=? WHERE id=?"
            );
            $stmt->bind_param('ssssi', $name, $slug, $description, $icon, $catId);
            $stmt->execute();
            setFlash('success', 'Категорията е обновена.');
        } else {
            $check = $db->prepare("SELECT id FROM categories WHERE slug=?");
            $check->bind_param('s', $slug);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = 'Категория с това наименование вече съществува.';
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO categories (name, slug, description, icon) VALUES (?,?,?,?)"
                );
                $stmt->bind_param('ssss', $name, $slug, $description, $icon);
                $stmt->execute();
                setFlash('success', 'Категория ' . $name . ' е добавена.');
            }
        }

        if (!$error) {
            header('Location: ' . SITE_URL . '/admin/categories.php');
            exit;
        }
    }
}

// ПРОМЕНИ ТОВА:
// Намери този блок и го замени целия:
$adminCategories = $db->query(
    "SELECT c.id, c.name, c.slug, c.description, c.icon,
            COUNT(p.id) AS product_count
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     WHERE p.is_active = 1 OR p.id IS NULL
     GROUP BY c.id
     ORDER BY c.name ASC"
)->fetch_all(MYSQLI_ASSOC);
$editCat = null;

?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Категории — Админ — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        html, body { background: #0a0a0a !important; color: #f5f5f0 !important; min-height: 100vh; }

        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 6px;
            padding: 8px;
            max-height: 220px;
            overflow-y: auto;
        }
        .emoji-btn {
            font-size: 1.4rem;
            padding: 6px;
            border-radius: var(--radius);
            cursor: pointer;
            text-align: center;
            background: var(--dark-3);
            border: 2px solid transparent;
            transition: all .15s ease;
            line-height: 1;
        }
        .emoji-btn:hover {
            background: var(--dark-4);
            transform: scale(1.2);
        }
        .emoji-btn.selected {
            border-color: var(--accent);
            background: var(--dark-4);
        }
       .icon-preview {
    font-size: 3.5rem;
    width: 90px;
    height: 90px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--dark-3);
    border: 2px solid var(--dark-4);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition);
    flex-shrink: 0;
    user-select: none;
    outline: none;
}
.icon-preview:hover {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(232,255,0,.1);
}

        /* Emoji picker popup */
        .emoji-picker-popup {
            display: none;
            position: fixed;
            z-index: 9999;
            background: var(--dark-2);
            border: 1px solid var(--dark-4);
            border-radius: var(--radius-lg);
            box-shadow: 0 8px 32px rgba(0,0,0,.6);
            width: 320px;
            overflow: hidden;
        }
        .emoji-picker-popup.open { display: block; }
        .emoji-picker-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--dark-4);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .emoji-picker-header input {
            flex: 1;
            padding: 6px 10px;
            background: var(--dark-3);
            border: 1px solid var(--dark-4);
            border-radius: var(--radius);
            color: var(--white);
            font-size: .9rem;
        }
        .emoji-picker-header input:focus {
            outline: none;
            border-color: var(--accent);
        }
        .emoji-picker-header span {
            color: var(--grey);
            font-size: .8rem;
            white-space: nowrap;
        }
        .custom-icon-input {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-top: 8px;
        }
        .custom-icon-input input {
            width: 70px !important;
            text-align: center;
            font-size: 1.4rem !important;
            padding: 8px !important;
        }
    </style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<div class="admin-layout">
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="admin-content">
        <h1 class="admin-title">📂 Категории</h1>

        <?php include 'includes/admin_flash.php'; ?>

        <div style="display:grid;grid-template-columns:400px 1fr;gap:32px;align-items:start;">

            <!-- ── ФОРМА ──────────────────────────────── -->
            <div style="background:var(--dark-2);border:1px solid var(--dark-3);
                        border-radius:var(--radius-lg);padding:28px;
                        position:sticky;top:calc(var(--header-h) + 16px);">
                <h2 style="font-size:1.4rem;color:var(--white);margin-bottom:20px;
                           padding-bottom:14px;border-bottom:1px solid var(--dark-4);">
                    <?= $editCat ? 'Редактирай категория' : 'Нова категория' ?>
                </h2>

                <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" id="catForm">
                    <?php if ($editCat): ?>
                    <input type="hidden" name="cat_id" value="<?= $editCat['id'] ?>">
                    <?php endif; ?>

                    <!-- Икона -->
<div class="form-group">
    <label>Икона / Емоджи</label>
    <div
        class="icon-preview"
        id="iconPreview"
        onclick="togglePicker(event)"
        title="Кликни за да избереш или постави емоджи">
        <?= e($_POST['icon'] ?? $editCat['icon'] ?? '🎛️') ?>
    </div>
    <input type="text"
           name="icon"
           id="iconInput"
           value="<?= e($_POST['icon'] ?? $editCat['icon'] ?? '🎛️') ?>"
           maxlength="4"
           oninput="updatePreview(this.value)"
           style="display:none;">
    <small style="color:var(--grey);font-size:.75rem;display:block;margin-top:6px;">
        Кликни иконата за picker или постави емоджи директно в нея
    </small>

    <!-- Emoji Picker Popup -->
    <div class="emoji-picker-popup" id="emojiPicker">
        <div class="emoji-picker-header">
            <span>Избери:</span>
            <input type="text"
                   id="emojiSearch"
                   placeholder="Търси..."
                   oninput="filterEmojis(this.value)">
        </div>
        <div class="emoji-grid" id="emojiGrid">
            <?php
            $emojis = [
                '🎛️','🎚️','🎧','🔊','💿','🎵','🎶','🎸',
                '🎹','🎺','🎻','🥁','🎤','🎼','🎙️','📻',
                '💾','📀','🖥️','⌨️','🖱️','📱','💻','🔌',
                '🔋','💡','🔦','🔈','📢','📣','🔔','🔕',
                '⚡','🌀','💫','✨','🌟','⭐','🔥','❄️',
                '🎯','🏆','🥇','🎖️','🏅','🎗️','🎪','🎠',
                '🎭','🎬','📽️','🎞️','📸','📷','🔭','🔬',
                '🧲','⚙️','🔧','🔩','🛠️','⛏️','🔑','🗝️',
                '🎮','🕹️','👾','🤖','👻','💀','☠️','🎃',
                '🌈','🌊','🌪️','🌙','☀️','🌤️','⛈️','🌀',
            ];
            $currentIcon = $_POST['icon'] ?? $editCat['icon'] ?? '🎛️';
            foreach ($emojis as $emoji):
            ?>
            <button type="button"
                    class="emoji-btn <?= $emoji === $currentIcon ? 'selected' : '' ?>"
                    data-emoji="<?= $emoji ?>"
                    onclick="selectEmoji('<?= $emoji ?>')">
                <?= $emoji ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

                    <!-- Наименование -->
                    <div class="form-group">
                        <label for="cat_name">Наименование</label>
                        <input type="text"
                               id="cat_name"
                               name="name"
                               value="<?= e($_POST['name'] ?? $editCat['name'] ?? '') ?>"
                               placeholder="DJ Конзоли"
                               required>
                    </div>

                    <!-- Описание -->
                    <div class="form-group">
                        <label for="cat_desc">Описание</label>
                        <textarea id="cat_desc"
                                  name="description"
                                  rows="3"
                                  placeholder="Кратко описание..."><?= e($_POST['description'] ?? $editCat['description'] ?? '') ?></textarea>
                    </div>

                    <div style="display:flex;gap:12px;">
                        <button type="submit" class="btn btn--primary">
                            <?= $editCat ? '💾 Запази' : '➕ Добави' ?>
                        </button>
                        <?php if ($editCat): ?>
                        <a href="<?= SITE_URL ?>/admin/categories.php"
                           class="btn btn--ghost">Отказ</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- ── ТАБЛИЦА ─────────────────────────────── -->
            <div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Икона</th>
                            <th>Категория</th>
                            <th>Slug</th>
                            <th>Продукти</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php if (empty($adminCategories)): ?>
    <tr>
        <td colspan="5"
            style="text-align:center;color:var(--grey);padding:32px;">
            Няма добавени категории.
        </td>
    </tr>
    <?php else: ?>
    <?php foreach ($adminCategories as $cat): ?>
    <tr>
        <td style="font-size:1.8rem;text-align:center;">
            <?= e($cat['icon'] ?? '🎛️') ?>
        </td>
        <td>
            <strong style="color:var(--white);">
                <?= e($cat['name']) ?>
            </strong>
            <?php if (!empty($cat['description'])): ?>
            <br>
            <small style="color:var(--grey);">
                <?= e(mb_substr($cat['description'], 0, 50)) ?>
            </small>
            <?php endif; ?>
        </td>
        <td>
            <code style="color:var(--accent);font-size:.8rem;
                         background:var(--dark-3);padding:2px 8px;
                         border-radius:3px;">
                <?= e($cat['slug']) ?>
            </code>
        </td>
        <td style="color:var(--grey-light);">
            <?= (int)$cat['product_count'] ?> бр.
        </td>
        <td>
            <div class="table-actions">
                <a href="?edit=<?= (int)$cat['id'] ?>"
                   class="btn-edit">✏ Редакция</a>
                <a href="?delete=<?= (int)$cat['id'] ?>"
                   class="btn-delete"
                   data-confirm="Изтрий тази категория?">
                    🗑 Изтрий
                </a>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
</tbody>
                </table>
            </div>

        </div>
    </main>
</div>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
// ── Emoji Picker ──────────────────────────────────────────────
const picker   = document.getElementById('emojiPicker');
const preview  = document.getElementById('iconPreview');
const input    = document.getElementById('iconInput');

function togglePicker(e) {
    e.stopPropagation();
    const rect = preview.getBoundingClientRect();

    if (picker.classList.contains('open')) {
        picker.classList.remove('open');
        return;
    }

    // Позиционирай под preview бутона
    picker.style.top  = (rect.bottom + window.scrollY + 8) + 'px';
    picker.style.left = rect.left + 'px';

    picker.classList.add('open');
    document.getElementById('emojiSearch').focus();

    // Поддръжка за paste директно в иконата
preview.addEventListener('paste', function(e) {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData('text');
    if (text.trim()) {
        selectEmoji(text.trim());
    }
});

// Поддръжка за въвеждане директно
preview.setAttribute('contenteditable', 'true');
preview.addEventListener('input', function() {
    const val = this.textContent.trim();
    if (val) {
        input.value = val;
    }
});
preview.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.blur();
    }
});
}

function selectEmoji(emoji) {
    input.value    = emoji;
    preview.textContent = emoji;
    picker.classList.remove('open');

    document.querySelectorAll('.emoji-btn').forEach(btn => {
        btn.classList.toggle('selected', btn.dataset.emoji === emoji);
    });
}

function updatePreview(val) {
    if (val.trim()) {
        preview.textContent = val.trim();
    }
}

function filterEmojis(query) {
    document.querySelectorAll('.emoji-btn').forEach(btn => {
        btn.style.display = btn.dataset.emoji.includes(query) || !query
            ? '' : 'none';
    });
}

// Затвори при клик извън
document.addEventListener('click', function(e) {
    if (!picker.contains(e.target) && e.target !== preview) {
        picker.classList.remove('open');
    }
});

// Затвори при Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') picker.classList.remove('open');
});
</script>
</body>
</html>