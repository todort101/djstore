<?php
// includes/cart.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Вземи кошницата ──────────────────────────────────────────
function getCart(): array {
    return $_SESSION['cart'] ?? [];
}

// ── Добави в кошница ─────────────────────────────────────────
function addToCart(int $productId, int $quantity = 1): void {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

// ── Промени количество ────────────────────────────────────────
function updateCart(int $productId, int $quantity): void {
    if ($quantity <= 0) {
        removeFromCart($productId);
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

// ── Премахни от кошница ───────────────────────────────────────
function removeFromCart(int $productId): void {
    unset($_SESSION['cart'][$productId]);
}

// ── Изчисти кошницата ─────────────────────────────────────────
function clearCart(): void {
    $_SESSION['cart']  = [];
    $_SESSION['promo'] = null;
}

// ── Брой артикули ─────────────────────────────────────────────
function getCartCount(): int {
    return array_sum(getCart());
}

// ── Промо код в сесия ─────────────────────────────────────────
function getSessionPromo(): ?array {
    return $_SESSION['promo'] ?? null;
}

function setSessionPromo(?array $promo): void {
    $_SESSION['promo'] = $promo;
}

function removeSessionPromo(): void {
    $_SESSION['promo'] = null;
}

// ── Детайли на кошницата ──────────────────────────────────────
function getCartDetails(): array {
    $cart = getCart();
    if (empty($cart)) return [];

    $db  = getDB();
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $res = $db->query(
        "SELECT id, name, price, image, stock, slug FROM products
         WHERE id IN ({$ids}) AND is_active = 1"
    );

    $items    = [];
    $subtotal = 0.0;

    while ($row = $res->fetch_assoc()) {
        $qty       = $cart[$row['id']];
        $itemTotal = $row['price'] * $qty;
        $subtotal += $itemTotal;
        $items[]   = [
            'product'  => $row,
            'quantity' => $qty,
            'subtotal' => $itemTotal,
        ];
    }

    // Доставка
    $shipping = $subtotal >= 200 ? 0 : 8.99;

    // Промо отстъпка
    $discount  = 0.0;
    $promoData = null;
    $promo     = getSessionPromo();

    if ($promo) {
        if ($promo['type'] === 'percent') {
            $discount = round($subtotal * $promo['value'] / 100, 2);
        } else {
            $discount = min((float)$promo['value'], $subtotal);
        }
        $promoData = $promo;
    }

    $total = max(0, $subtotal - $discount) + $shipping;

    return [
        'items'    => $items,
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'discount' => $discount,
        'promo'    => $promoData,
        'total'    => $total,
    ];
}

// ── Създай поръчка ────────────────────────────────────────────
function createOrder(int $userId, array $shipping, string $notes = ''): int|false {
    $db      = getDB();
    $details = getCartDetails();
    if (empty($details['items'])) return false;

    $promoCode      = $details['promo']['code'] ?? null;
    $discountAmount = $details['discount']       ?? 0;

    $db->begin_transaction();
    try {
        $stmt = $db->prepare(
            "INSERT INTO orders
             (user_id, total_amount, shipping_name, shipping_phone,
              shipping_address, notes, promo_code, discount_amount)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'idsssssd',
            $userId,
            $details['total'],
            $shipping['name'],
            $shipping['phone'],
            $shipping['address'],
            $notes,
            $promoCode,
            $discountAmount
        );
        $stmt->execute();
        $orderId = $db->insert_id;

        $stmt2 = $db->prepare(
            "INSERT INTO order_items
             (order_id, product_id, product_name, price, quantity)
             VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($details['items'] as $item) {
            $pid   = $item['product']['id'];
            $name  = $item['product']['name'];
            $price = $item['product']['price'];
            $qty   = $item['quantity'];
            $stmt2->bind_param('iisdi', $orderId, $pid, $name, $price, $qty);
            $stmt2->execute();

            // Намали наличността
            $db->query(
                "UPDATE products SET stock = stock - {$qty} WHERE id = {$pid}"
            );
        }

        // Увеличи брояча на промо кода
        if ($promoCode) {
            incrementPromoUsage($promoCode, $userId, $orderId);
            removeSessionPromo();
        }

        $db->commit();
        clearCart();
        return $orderId;

    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}
?>