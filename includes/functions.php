<?php
// includes/functions.php

require_once __DIR__ . '/../config/database.php';

// ── Slug генератор ──────────────────────────────────────────────
function createSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $cyr  = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м',
              'н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ',
              'ы','ь','э','ю','я',
              'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М',
              'Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ',
              'Ы','Ь','Э','Ю','Я'];
    $lat  = ['a','b','v','g','d','e','e','zh','z','i','j','k','l','m',
              'n','o','p','r','s','t','u','f','h','ts','ch','sh','sht','a',
              'i','','e','ju','ja',
              'A','B','V','G','D','E','E','Zh','Z','I','J','K','L','M',
              'N','O','P','R','S','T','U','F','H','Ts','Ch','Sh','Sht','A',
              'I','','E','Ju','Ja'];
    $text = str_replace($cyr, $lat, $text);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return $text;
}

// ── Flash съобщения ─────────────────────────────────────────────
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash(): void {
    $flash = getFlash();
    if (!$flash) return;
    $type = htmlspecialchars($flash['type']);
    $msg  = htmlspecialchars($flash['message']);
    echo "<div class=\"alert alert-{$type}\">{$msg}</div>";
}

// ── Escape ──────────────────────────────────────────────────────
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ── Форматиране на цена ─────────────────────────────────────────
function formatPrice(float $price): string {
    return '€' . number_format($price, 2, '.', ' ');
}

// ── Категории ───────────────────────────────────────────────────
function getAllCategories(): array {
    $db     = getDB();
    $result = $db->query("SELECT * FROM categories ORDER BY name ASC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getCategoryBySlug(string $slug): ?array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

// ── Продукти ────────────────────────────────────────────────────
function getProducts(array $filters = []): array {
    $db     = getDB();
    $where  = ['p.is_active = 1'];
    $params = [];
    $types  = '';

    if (!empty($filters['category_id'])) {
        $where[]  = 'p.category_id = ?';
        $params[] = (int)$filters['category_id'];
        $types   .= 'i';
    }
    if (!empty($filters['search'])) {
        $where[]  = '(p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)';
        $s        = '%' . $filters['search'] . '%';
        $params   = array_merge($params, [$s, $s, $s]);
        $types   .= 'sss';
    }
    if (!empty($filters['min_price'])) {
        $where[]  = 'p.price >= ?';
        $params[] = (float)$filters['min_price'];
        $types   .= 'd';
    }
    if (!empty($filters['max_price'])) {
        $where[]  = 'p.price <= ?';
        $params[] = (float)$filters['max_price'];
        $types   .= 'd';
    }

    $sort = match($filters['sort'] ?? '') {
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name_asc'   => 'p.name ASC',
        default      => 'p.created_at DESC',
    };

    $sql = "SELECT p.*, c.name AS category_name
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$sort}";

    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getProductBySlug(string $slug): ?array {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT p.*, c.name AS category_name
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.slug = ? AND p.is_active = 1"
    );
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function getProductById(int $id): ?array {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT p.*, c.name AS category_name
         FROM products p
         JOIN categories c ON c.id = p.category_id
         WHERE p.id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

// ── Снимка на продукт ───────────────────────────────────────────
function productImage(?string $image): string {
    if ($image && file_exists(UPLOAD_DIR . $image)) {
        return UPLOAD_URL . e($image);
    }
    return 'https://placehold.co/400x400/1a1a1a/888888?text=No+Image';
}

// ── Поръчки ─────────────────────────────────────────────────────
function getOrdersByUser(int $userId): array {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC"
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getOrderItems(int $orderId): array {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT oi.*, p.slug AS product_slug
         FROM order_items oi
         LEFT JOIN products p ON p.id = oi.product_id
         WHERE oi.order_id = ?"
    );
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getOrderStatus(string $status): string {
    return match($status) {
        'pending'    => '⏳ Изчакваща',
        'processing' => '🔧 В обработка',
        'shipped'    => '🚚 Изпратена',
        'delivered'  => '✅ Доставена',
        'cancelled'  => '❌ Отказана',
        default      => $status,
    };
}

// ════════════════════════════════════════════════════════════════
// ПРОМО КОДОВЕ
// ════════════════════════════════════════════════════════════════

// ── Провери промо код ─────────────────────────────────────────
function validatePromoCode(string $code, float $orderTotal, ?int $userId = null): array {
    $db   = getDB();
    $code = strtoupper(trim($code));

    $stmt = $db->prepare(
        "SELECT * FROM promo_codes
         WHERE code = ?
           AND is_active = 1
           AND (expires_at IS NULL OR expires_at >= CURDATE())
           AND (max_uses IS NULL OR used_count < max_uses)"
    );
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $promo = $stmt->get_result()->fetch_assoc();

    if (!$promo) {
        return ['valid' => false, 'error' => 'Невалиден или изтекъл промо код.'];
    }

    // Провери минимална поръчка
    if ($orderTotal < $promo['min_order']) {
        $min = formatPrice($promo['min_order']);
        return ['valid' => false, 'error' => 'Минималната поръчка за този код е ' . $min . '.'];
    }

    // Провери per_user_limit ако потребителят е логнат
    if ($promo['per_user_limit'] && $userId) {
        $stmt2 = $db->prepare(
            "SELECT COUNT(*) FROM promo_code_usage
             WHERE promo_code_id = ? AND user_id = ?"
        );
        $stmt2->bind_param('ii', $promo['id'], $userId);
        $stmt2->execute();
        $usageCount = (int)$stmt2->get_result()->fetch_row()[0];

        if ($usageCount >= $promo['per_user_limit']) {
            return [
                'valid' => false,
                'error' => 'Вече си използвал този промо код.',
            ];
        }
    }

    // Изчисли отстъпката
    if ($promo['type'] === 'percent') {
        $discount = round($orderTotal * $promo['value'] / 100, 2);
    } else {
        $discount = min((float)$promo['value'], $orderTotal);
    }

    return [
        'valid'    => true,
        'promo'    => $promo,
        'discount' => $discount,
        'label'    => $promo['type'] === 'percent'
                      ? '-' . $promo['value'] . '%'
                      : '-' . formatPrice($promo['value']),
    ];
}

// ── Вземи промо код по ID ─────────────────────────────────────
function getPromoById(int $id): ?array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM promo_codes WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

// ── Всички промо кодове (за админ) ───────────────────────────
function getAllPromoCodes(): array {
    $db = getDB();
    return $db->query(
        "SELECT * FROM promo_codes ORDER BY created_at DESC"
    )->fetch_all(MYSQLI_ASSOC);
}

// ── Увеличи брояча на използвания ────────────────────────────
function incrementPromoUsage(string $code, ?int $userId = null, ?int $orderId = null): void {
    $db   = getDB();
    $code = strtoupper(trim($code));

    // Увеличи общия брояч
    $db->query(
        "UPDATE promo_codes SET used_count = used_count + 1 WHERE code = '$code'"
    );

    // Запиши usage по потребител ако е логнат
    if ($userId) {
        $stmt = $db->prepare("SELECT id FROM promo_codes WHERE code = ?");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $promo = $stmt->get_result()->fetch_assoc();

        if ($promo) {
            $stmt2 = $db->prepare(
                "INSERT IGNORE INTO promo_code_usage
                 (promo_code_id, user_id, order_id)
                 VALUES (?, ?, ?)"
            );
            $stmt2->bind_param('iii', $promo['id'], $userId, $orderId);
            $stmt2->execute();
        }
    }
}

// ── Статус на промо код ───────────────────────────────────────
function promoStatus(array $promo): string {
    if (!$promo['is_active'])
        return '<span class="status-badge status-cancelled">Неактивен</span>';
    if ($promo['expires_at'] && $promo['expires_at'] < date('Y-m-d'))
        return '<span class="status-badge status-cancelled">Изтекъл</span>';
    if ($promo['max_uses'] && $promo['used_count'] >= $promo['max_uses'])
        return '<span class="status-badge status-cancelled">Изчерпан</span>';
    return '<span class="status-badge status-delivered">Активен</span>';
}
?>