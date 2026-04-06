<?php
// admin/index.php
$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin();
requireAdmin();

$db = getDB();

// ── Основни статистики ─────────────────────────────────────────
$totalProducts = $db->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetch_row()[0];
$totalOrders   = $db->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$totalUsers    = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$totalRevenue  = $db->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'")->fetch_row()[0];
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0];
$deliveredOrders = $db->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetch_row()[0];

// ── Приходи по дни — последните 30 дни ────────────────────────
$revenueByDay = $db->query(
    "SELECT
        DATE(created_at) AS day,
        COALESCE(SUM(total_amount), 0) AS revenue,
        COUNT(*) AS order_count
     FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
       AND status != 'cancelled'
     GROUP BY DATE(created_at)
     ORDER BY day ASC"
)->fetch_all(MYSQLI_ASSOC);

// Попълни липсващите дни с 0
$revenueMap = [];
foreach ($revenueByDay as $row) {
    $revenueMap[$row['day']] = [
        'revenue'     => (float)$row['revenue'],
        'order_count' => (int)$row['order_count'],
    ];
}
$days30Labels  = [];
$days30Revenue = [];
$days30Orders  = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $days30Labels[]  = date('d.m', strtotime($date));
    $days30Revenue[] = $revenueMap[$date]['revenue']     ?? 0;
    $days30Orders[]  = $revenueMap[$date]['order_count'] ?? 0;
}

// ── Поръчки по статус ──────────────────────────────────────────
$ordersByStatus = $db->query(
    "SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status"
)->fetch_all(MYSQLI_ASSOC);

$statusLabels = [];
$statusData   = [];
$statusColors = [
    'pending'    => '#ffaa00',
    'processing' => '#44aaff',
    'shipped'    => '#cc88ff',
    'delivered'  => '#00cc66',
    'cancelled'  => '#ff4444',
];
foreach ($ordersByStatus as $row) {
    $statusLabels[] = getOrderStatus($row['status']);
    $statusData[]   = (int)$row['cnt'];
}

// ── Топ 5 продукти по продажби ─────────────────────────────────
$topProducts = $db->query(
    "SELECT
        p.name,
        SUM(oi.quantity) AS total_qty,
        SUM(oi.quantity * oi.price) AS total_revenue
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     JOIN orders o   ON o.id = oi.order_id
     WHERE o.status != 'cancelled'
     GROUP BY oi.product_id
     ORDER BY total_qty DESC
     LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

// ── Приходи по месец — последните 6 месеца ─────────────────────
$revenueByMonth = $db->query(
    "SELECT
        DATE_FORMAT(created_at, '%Y-%m') AS month,
        DATE_FORMAT(created_at, '%m/%Y') AS month_label,
        COALESCE(SUM(total_amount), 0)   AS revenue
     FROM orders
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
       AND status != 'cancelled'
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY month ASC"
)->fetch_all(MYSQLI_ASSOC);

$monthLabels  = array_column($revenueByMonth, 'month_label');
$monthRevenue = array_map(fn($r) => (float)$r['revenue'], $revenueByMonth);

// ── Нови потребители по месец ──────────────────────────────────
$usersByMonth = $db->query(
    "SELECT
        DATE_FORMAT(created_at, '%m/%Y') AS month_label,
        COUNT(*) AS cnt
     FROM users
     WHERE role = 'user'
       AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
     ORDER BY created_at ASC"
)->fetch_all(MYSQLI_ASSOC);

$userMonthLabels = array_column($usersByMonth, 'month_label');
$userMonthData   = array_map(fn($r) => (int)$r['cnt'], $usersByMonth);

// ── Последни 5 поръчки ─────────────────────────────────────────
$recentOrders = $db->query(
    "SELECT o.*, u.username, u.full_name
     FROM orders o
     JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ табло — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <style>
        html, body { background: #0a0a0a !important; color: #f5f5f0 !important; min-height: 100vh; }

        /* ── Chart контейнери ── */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        .charts-grid--3 {
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        .chart-card {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: var(--radius-lg);
            padding: 24px;
            position: relative;
        }
        .chart-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .chart-card__title {
            font-family: var(--font-condensed);
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--white);
        }
        .chart-card__sub {
            font-size: .78rem;
            color: var(--grey);
            font-family: var(--font-condensed);
            letter-spacing: .06em;
        }
        .chart-wrap {
            position: relative;
            width: 100%;
        }
        .chart-wrap--sm { height: 200px; }
        .chart-wrap--md { height: 260px; }
        .chart-wrap--lg { height: 300px; }

        /* ── Топ продукти ── */
        .top-product {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--dark-3);
        }
        .top-product:last-child { border-bottom: none; }
        .top-product__rank {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--dark-3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-display);
            font-size: .9rem;
            color: var(--accent);
            flex-shrink: 0;
        }
        .top-product__rank--1 { background: rgba(232,255,0,.15); }
        .top-product__rank--2 { background: rgba(200,200,200,.1); }
        .top-product__rank--3 { background: rgba(200,130,0,.1); }
        .top-product__name {
            flex: 1;
            font-size: .88rem;
            color: var(--white);
            line-height: 1.3;
        }
        .top-product__stats {
            text-align: right;
            flex-shrink: 0;
        }
        .top-product__qty {
            font-family: var(--font-condensed);
            font-weight: 700;
            color: var(--accent);
            font-size: .95rem;
        }
        .top-product__revenue {
            font-size: .75rem;
            color: var(--grey);
        }

        /* ── Stat cards ── */
        .stat-card::before { background: var(--accent); }
        .stat-card--blue::before   { background: #44aaff; }
        .stat-card--green::before  { background: #00cc66; }
        .stat-card--purple::before { background: #cc88ff; }
        .stat-card--orange::before { background: #ffaa00; }

        /* ── Period tabs ── */
        .period-tabs {
            display: flex;
            gap: 4px;
            background: var(--dark-3);
            border-radius: var(--radius);
            padding: 3px;
        }
        .period-tab {
            padding: 4px 12px;
            font-family: var(--font-condensed);
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .06em;
            color: var(--grey);
            border-radius: 3px;
            cursor: pointer;
            border: none;
            background: none;
            transition: all var(--transition);
        }
        .period-tab.active {
            background: var(--accent);
            color: var(--black);
        }

        @media (max-width: 1024px) {
            .charts-grid,
            .charts-grid--3 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<div class="admin-layout">
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="admin-content">

        <!-- Заглавие -->
        <h1 class="admin-title">
            📊 Табло
            <span style="font-size:1rem;color:var(--grey);font-family:var(--font-body);font-weight:400;">
                Добре дошъл, <?= e($_SESSION['full_name']) ?>
            </span>
        </h1>

        <?php include 'includes/admin_flash.php'; ?>

        <!-- ── STAT CARDS ───────────────────────────────────── -->
        <div class="admin-stats" style="margin-bottom:32px;">
            <div class="stat-card">
                <div class="stat-card__icon">💰</div>
                <div class="stat-card__value">
                    €<?= number_format($totalRevenue, 0, '.', ' ') ?>
                </div>
                <div class="stat-card__label">Общи приходи</div>
            </div>
            <div class="stat-card stat-card--blue">
                <div class="stat-card__icon">📦</div>
                <div class="stat-card__value"><?= $totalOrders ?></div>
                <div class="stat-card__label">Общо поръчки</div>
            </div>
            <div class="stat-card stat-card--green">
                <div class="stat-card__icon">✅</div>
                <div class="stat-card__value"><?= $deliveredOrders ?></div>
                <div class="stat-card__label">Доставени</div>
            </div>
            <div class="stat-card stat-card--orange">
                <div class="stat-card__icon">⏳</div>
                <div class="stat-card__value"><?= $pendingOrders ?></div>
                <div class="stat-card__label">Изчакващи</div>
            </div>
            <div class="stat-card stat-card--purple">
                <div class="stat-card__icon">🎛️</div>
                <div class="stat-card__value"><?= $totalProducts ?></div>
                <div class="stat-card__label">Активни продукти</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__icon">👥</div>
                <div class="stat-card__value"><?= $totalUsers ?></div>
                <div class="stat-card__label">Потребители</div>
            </div>
        </div>

        <!-- ── ПРИХОДИ ПО ДНИ ───────────────────────────────── -->
        <div class="chart-card" style="margin-bottom:24px;">
            <div class="chart-card__header">
                <div>
                    <p class="chart-card__title">📈 Приходи и поръчки</p>
                    <p class="chart-card__sub">Последните 30 дни</p>
                </div>
                <div class="period-tabs">
                    <button class="period-tab active" onclick="showDataset('revenue')">
                        Приходи
                    </button>
                    <button class="period-tab" onclick="showDataset('orders')">
                        Поръчки
                    </button>
                </div>
            </div>
            <div class="chart-wrap chart-wrap--lg">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- ── СРЕДЕН РЕД: Статуси + Топ продукти ───────────── -->
        <div class="charts-grid--3">

            <!-- Поръчки по статус -->
            <div class="chart-card">
                <div class="chart-card__header">
                    <div>
                        <p class="chart-card__title">🥧 Поръчки по статус</p>
                        <p class="chart-card__sub">Всички поръчки</p>
                    </div>
                </div>
                <?php if (empty($ordersByStatus)): ?>
                <div style="text-align:center;padding:40px;color:var(--grey);">
                    Няма поръчки все още
                </div>
                <?php else: ?>
                <div class="chart-wrap chart-wrap--md">
                    <canvas id="statusChart"></canvas>
                </div>
                <?php endif; ?>
            </div>

            <!-- Топ продукти -->
            <div class="chart-card">
                <div class="chart-card__header">
                    <div>
                        <p class="chart-card__title">🏆 Топ продукти</p>
                        <p class="chart-card__sub">По брой продажби</p>
                    </div>
                </div>
                <?php if (empty($topProducts)): ?>
                <div style="text-align:center;padding:40px;color:var(--grey);">
                    Няма продажби все още
                </div>
                <?php else: ?>
                <div>
                    <?php foreach ($topProducts as $i => $tp): ?>
                    <div class="top-product">
                        <div class="top-product__rank top-product__rank--<?= $i + 1 ?>">
                            <?= $i + 1 ?>
                        </div>
                        <div class="top-product__name">
                            <?= e($tp['name']) ?>
                        </div>
                        <div class="top-product__stats">
                            <div class="top-product__qty">
                                <?= $tp['total_qty'] ?> бр.
                            </div>
                            <div class="top-product__revenue">
                                <?= formatPrice($tp['total_revenue']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- ── ДОЛЕН РЕД: Месечни приходи + Потребители ─────── -->
        <div class="charts-grid">

            <!-- Месечни приходи -->
            <div class="chart-card">
                <div class="chart-card__header">
                    <div>
                        <p class="chart-card__title">📅 Месечни приходи</p>
                        <p class="chart-card__sub">Последните 6 месеца</p>
                    </div>
                </div>
                <?php if (empty($monthLabels)): ?>
                <div style="text-align:center;padding:40px;color:var(--grey);">
                    Няма данни все още
                </div>
                <?php else: ?>
                <div class="chart-wrap chart-wrap--md">
                    <canvas id="monthChart"></canvas>
                </div>
                <?php endif; ?>
            </div>

            <!-- Нови потребители -->
            <div class="chart-card">
                <div class="chart-card__header">
                    <div>
                        <p class="chart-card__title">👥 Нови потребители</p>
                        <p class="chart-card__sub">Последните 6 месеца</p>
                    </div>
                </div>
                <?php if (empty($userMonthLabels)): ?>
                <div style="text-align:center;padding:40px;color:var(--grey);">
                    Няма данни все още
                </div>
                <?php else: ?>
                <div class="chart-wrap chart-wrap--md">
                    <canvas id="usersChart"></canvas>
                </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- ── ПОСЛЕДНИ ПОРЪЧКИ ──────────────────────────────── -->
        <div style="margin-top:8px;">
            <div style="display:flex;align-items:center;justify-content:space-between;
                        margin-bottom:16px;flex-wrap:wrap;gap:12px;">
                <h2 style="font-size:1.8rem;color:var(--white);">Последни поръчки</h2>
                <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn--ghost btn--sm">
                    Виж всички →
                </a>
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
                    <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="6"
                            style="text-align:center;color:var(--grey);padding:32px;">
                            Няма поръчки все още.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentOrders as $o): ?>
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
                            <a href="<?= SITE_URL ?>/admin/orders.php?id=<?= $o['id'] ?>"
                               class="btn-edit">Виж →</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>

<script>
// ── Глобална Chart.js конфигурация ────────────────────────────
Chart.defaults.color          = '#888888';
Chart.defaults.borderColor    = '#2e2e2e';
Chart.defaults.font.family    = 'Barlow, sans-serif';

const ACCENT  = '#e8ff00';
const BLUE    = '#44aaff';
const GREEN   = '#00cc66';
const PURPLE  = '#cc88ff';
const ORANGE  = '#ffaa00';
const RED     = '#ff4444';
const DARK_3  = '#242424';

// ── Данни от PHP ──────────────────────────────────────────────
const days30Labels  = <?= json_encode($days30Labels) ?>;
const days30Revenue = <?= json_encode($days30Revenue) ?>;
const days30Orders  = <?= json_encode($days30Orders) ?>;
const statusLabels  = <?= json_encode($statusLabels) ?>;
const statusData    = <?= json_encode($statusData) ?>;
const monthLabels   = <?= json_encode($monthLabels) ?>;
const monthRevenue  = <?= json_encode($monthRevenue) ?>;
const userLabels    = <?= json_encode($userMonthLabels) ?>;
const userData      = <?= json_encode($userMonthData) ?>;

// ── Помощни функции ───────────────────────────────────────────
function gradientFill(ctx, color) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0,   color + '33');
    gradient.addColorStop(1,   color + '00');
    return gradient;
}

function tooltipStyle() {
    return {
        backgroundColor: '#1a1a1a',
        borderColor:     '#2e2e2e',
        borderWidth:     1,
        titleColor:      '#f5f5f0',
        bodyColor:       '#cccccc',
        padding:         12,
        cornerRadius:    6,
        displayColors:   false,
    };
}

// ════════════════════════════════════════════════════════════════
// 1. ГРАФИКА — Приходи по дни
// ════════════════════════════════════════════════════════════════
const revenueCtx = document.getElementById('revenueChart').getContext('2d');

const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: days30Labels,
        datasets: [
            {
                label:           'Приходи (€)',
                data:            days30Revenue,
                borderColor:     ACCENT,
                backgroundColor: gradientFill(revenueCtx, ACCENT),
                borderWidth:     2,
                pointRadius:     3,
                pointHoverRadius:6,
                pointBackgroundColor: ACCENT,
                tension:         0.4,
                fill:            true,
            },
            {
                label:           'Поръчки',
                data:            days30Orders,
                borderColor:     BLUE,
                backgroundColor: gradientFill(revenueCtx, BLUE),
                borderWidth:     2,
                pointRadius:     3,
                pointHoverRadius:6,
                pointBackgroundColor: BLUE,
                tension:         0.4,
                fill:            true,
                hidden:          true,
            }
        ]
    },
    options: {
        responsive:          true,
        maintainAspectRatio: false,
        interaction: {
            mode:      'index',
            intersect: false,
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                ...tooltipStyle(),
                callbacks: {
                    label: ctx => {
                        if (ctx.datasetIndex === 0) {
                            return ' €' + ctx.parsed.y.toFixed(2);
                        }
                        return ' ' + ctx.parsed.y + ' поръчки';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { color: '#1a1a1a' },
                ticks: {
                    maxTicksLimit: 10,
                    font: { size: 11 }
                }
            },
            y: {
                grid: { color: '#1a1a1a' },
                beginAtZero: true,
                ticks: {
                    font: { size: 11 },
                    callback: v => '€' + v
                }
            }
        }
    }
});

// Превключване между Приходи / Поръчки
function showDataset(type) {
    document.querySelectorAll('.period-tab').forEach((btn, i) => {
        btn.classList.toggle('active',
            (type === 'revenue' && i === 0) ||
            (type === 'orders'  && i === 1)
        );
    });
    revenueChart.data.datasets[0].hidden = (type === 'orders');
    revenueChart.data.datasets[1].hidden = (type === 'revenue');

    // Смени Y axis формата
    revenueChart.options.scales.y.ticks.callback =
        type === 'revenue'
            ? v => '€' + v
            : v => v + ' бр.';

    revenueChart.update();
}

// ════════════════════════════════════════════════════════════════
// 2. ГРАФИКА — Поръчки по статус (Doughnut)
// ════════════════════════════════════════════════════════════════
<?php if (!empty($ordersByStatus)): ?>
const statusCtx = document.getElementById('statusChart').getContext('2d');

const statusColorMap = {
    '⏳ Изчакваща': ORANGE,
    '🔧 В обработка': BLUE,
    '🚚 Изпратена': PURPLE,
    '✅ Доставена': GREEN,
    '❌ Отказана': RED,
};

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data:            statusData,
            backgroundColor: statusLabels.map(l => (statusColorMap[l] || ACCENT) + 'cc'),
            borderColor:     statusLabels.map(l => statusColorMap[l] || ACCENT),
            borderWidth:     2,
            hoverOffset:     8,
        }]
    },
    options: {
        responsive:          true,
        maintainAspectRatio: false,
        cutout:              '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding:   16,
                    font:      { size: 11 },
                    boxWidth:  12,
                    boxHeight: 12,
                }
            },
            tooltip: {
                ...tooltipStyle(),
                callbacks: {
                    label: ctx => ' ' + ctx.parsed + ' поръчки'
                }
            }
        }
    }
});
<?php endif; ?>

// ════════════════════════════════════════════════════════════════
// 3. ГРАФИКА — Месечни приходи (Bar)
// ════════════════════════════════════════════════════════════════
<?php if (!empty($monthLabels)): ?>
const monthCtx = document.getElementById('monthChart').getContext('2d');

new Chart(monthCtx, {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [{
            label:           'Приходи (€)',
            data:            monthRevenue,
            backgroundColor: ACCENT + '99',
            borderColor:     ACCENT,
            borderWidth:     2,
            borderRadius:    6,
            borderSkipped:   false,
        }]
    },
    options: {
        responsive:          true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                ...tooltipStyle(),
                callbacks: {
                    label: ctx => ' €' + ctx.parsed.y.toFixed(2)
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 } }
            },
            y: {
                grid:        { color: '#1a1a1a' },
                beginAtZero: true,
                ticks: {
                    font:     { size: 11 },
                    callback: v => '€' + v
                }
            }
        }
    }
});
<?php endif; ?>

// ════════════════════════════════════════════════════════════════
// 4. ГРАФИКА — Нови потребители (Line)
// ════════════════════════════════════════════════════════════════
<?php if (!empty($userMonthLabels)): ?>
const usersCtx = document.getElementById('usersChart').getContext('2d');

new Chart(usersCtx, {
    type: 'line',
    data: {
        labels: userLabels,
        datasets: [{
            label:           'Нови потребители',
            data:            userData,
            borderColor:     GREEN,
            backgroundColor: gradientFill(usersCtx, GREEN),
            borderWidth:     2,
            pointRadius:     5,
            pointHoverRadius:8,
            pointBackgroundColor: GREEN,
            tension:         0.4,
            fill:            true,
        }]
    },
    options: {
        responsive:          true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                ...tooltipStyle(),
                callbacks: {
                    label: ctx => ' ' + ctx.parsed.y + ' нови потребители'
                }
            }
        },
        scales: {
            x: {
                grid:  { display: false },
                ticks: { font: { size: 11 } }
            },
            y: {
                grid:        { color: '#1a1a1a' },
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    font:     { size: 11 }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

</body>
</html>