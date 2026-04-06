<?php
// pages/privacy.php
$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/includes/functions.php';
require_once $root . '/includes/auth.php';
require_once $root . '/includes/cart.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Политика на поверителност — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        html, body { background: #0a0a0a !important; color: #f5f5f0 !important; min-height: 100vh; }

        .legal-page {
            padding: 0px 0 100px;
        }
        .legal-header {
            padding: 40px 0 24px;
            border-bottom: 1px solid var(--dark-4);
            margin-bottom: 32px;
        }
        .legal-label {
            display: inline-block;
            font-family: var(--font-condensed);
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 16px;
            padding: 4px 12px;
            border: 1px solid rgba(232,255,0,.3);
            border-radius: 2px;
        }
        .legal-title {
            font-size: clamp(2.5rem, 5vw, 5rem);
            color: var(--white);
            margin-bottom: 16px;
        }
        .legal-updated {
            color: var(--grey);
            font-size: .9rem;
        }
        .legal-content {
            max-width: 800px;
        }
        .legal-content h2 {
            font-size: 1.8rem;
            color: var(--white);
            margin: 48px 0 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--dark-4);
        }
        .legal-content h3 {
            font-size: 1.2rem;
            color: var(--accent);
            margin: 28px 0 10px;
            font-family: var(--font-condensed);
            letter-spacing: .06em;
        }
        .legal-content p {
            color: var(--grey-light);
            line-height: 1.85;
            margin-bottom: 16px;
            font-size: .97rem;
        }
        .legal-content ul {
            list-style: none;
            margin-bottom: 16px;
        }
        .legal-content ul li {
            color: var(--grey-light);
            line-height: 1.8;
            font-size: .97rem;
            padding: 6px 0 6px 20px;
            position: relative;
            border-bottom: 1px solid var(--dark-3);
        }
        .legal-content ul li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: var(--accent);
            font-size: .85rem;
        }
        .legal-content ul li:last-child {
            border-bottom: none;
        }
        .legal-highlight {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-left: 3px solid var(--accent);
            border-radius: var(--radius);
            padding: 20px 24px;
            margin: 24px 0;
        }
        .legal-highlight p {
            margin-bottom: 0;
            color: var(--grey-light);
        }
        .legal-contact {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: var(--radius-lg);
            padding: 32px;
            margin-top: 48px;
        }
        .legal-contact h3 {
            color: var(--white) !important;
            font-size: 1.4rem !important;
            margin-top: 0 !important;
        }
        .legal-contact p {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<div class="legal-header">
    <div class="container">
        <span class="legal-label">Правна информация</span>
        <h1 class="legal-title">ПОЛИТИКА НА<br>ПОВЕРИТЕЛНОСТ</h1>
        <p class="legal-updated">Последна актуализация: <?= date('d.m.Y') ?></p>
    </div>
</div>

<main class="legal-page">
    <div class="container">
        <div class="legal-content">

            <div class="legal-highlight">
                <p>
                    Вашата поверителност е важна за нас. Тази политика описва какви данни
                    събираме, как ги използваме и как ги защитаваме при използване на
                    уебсайта и услугите на DJ Store.
                </p>
            </div>

            <!-- 1 -->
            <h2>1. Администратор на данните</h2>
            <p>
                Администратор на личните ви данни е <strong style="color:var(--white)">DJ Store</strong>,
                достъпен на адрес <strong style="color:var(--accent)"><?= SITE_URL ?></strong>.
            </p>
            <p>
                За въпроси относно обработката на лични данни можете да се свържете с нас на:
                <strong style="color:var(--white)">info@djstore.bg</strong>
            </p>

            <!-- 2 -->
            <h2>2. Какви данни събираме</h2>

            <h3>2.1 Данни при регистрация</h3>
            <ul>
                <li>Пълно ime и потребителско ime</li>
                <li>Имейл адрес</li>
                <li>Парола (съхранявана в криптиран вид)</li>
                <li>Телефонен номер (по желание)</li>
                <li>Адрес за доставка (по желание)</li>
            </ul>

            <h3>2.2 Данни при поръчка</h3>
            <ul>
                <li>Три имена на получателя</li>
                <li>Адрес за доставка</li>
                <li>Телефонен номер</li>
                <li>Информация за поръчаните продукти</li>
                <li>Бележки към поръчката</li>
            </ul>

            <h3>2.3 Данни при плащане</h3>
            <p>
                Плащанията с карта се обработват от <strong style="color:var(--white)">Stripe, Inc.</strong>
                DJ Store не съхранява данни за платежни карти. Stripe е сертифициран
                доставчик на платежни услуги, отговарящ на стандарта PCI DSS Level 1.
            </p>

            <h3>2.4 Технически данни</h3>
            <ul>
                <li>IP адрес</li>
                <li>Тип браузър и операционна система</li>
                <li>Дата и час на посещение</li>
                <li>Посетени страници</li>
            </ul>

            <!-- 3 -->
            <h2>3. Цел на обработката</h2>
            <p>Събираме и обработваме личните ви данни за следните цели:</p>
            <ul>
                <li>Създаване и управление на потребителски акаунт</li>
                <li>Обработка и доставка на поръчки</li>
                <li>Комуникация относно статуса на поръчки</li>
                <li>Осигуряване на сигурност на платформата</li>
                <li>Спазване на законови задължения</li>
                <li>Подобряване на услугите ни</li>
            </ul>

            <!-- 4 -->
            <h2>4. Правно основание</h2>
            <p>Обработката на личните ви данни се основава на:</p>
            <ul>
                <li><strong style="color:var(--white)">Договорно задължение</strong> — за изпълнение на поръчки</li>
                <li><strong style="color:var(--white)">Съгласие</strong> — при регистрация в платформата</li>
                <li><strong style="color:var(--white)">Законово задължение</strong> — за счетоводни и данъчни цели</li>
                <li><strong style="color:var(--white)">Легитимен интерес</strong> — за сигурност и предотвратяване на измами</li>
            </ul>

            <!-- 5 -->
            <h2>5. Споделяне на данни</h2>
            <p>
                DJ Store не продава и не отдава под наем личните ви данни на трети страни.
                Данните могат да бъдат споделяни само в следните случаи:
            </p>
            <ul>
                <li><strong style="color:var(--white)">Stripe</strong> — за обработка на плащания с карта</li>
                <li><strong style="color:var(--white)">Куриерски фирми</strong> — за доставка на поръчки (Speedy, Econt)</li>
                <li><strong style="color:var(--white)">Държавни органи</strong> — при законово задължение</li>
            </ul>

            <!-- 6 -->
            <h2>6. Съхранение на данните</h2>
            <p>
                Личните ви данни се съхраняват за периода, необходим за изпълнение на целите,
                за които са събрани:
            </p>
            <ul>
                <li>Данни на акаунт — до изтриване на акаунта</li>
                <li>Данни за поръчки — 5 години (законово изискване)</li>
                <li>Технически данни — до 12 месеца</li>
            </ul>

            <!-- 7 -->
            <h2>7. Вашите права</h2>
            <p>Съгласно GDPR имате следните права:</p>
            <ul>
                <li><strong style="color:var(--white)">Право на достъп</strong> — да получите копие на данните си</li>
                <li><strong style="color:var(--white)">Право на коригиране</strong> — да поправите неточни данни</li>
                <li><strong style="color:var(--white)">Право на изтриване</strong> — да поискате изтриване на данните си</li>
                <li><strong style="color:var(--white)">Право на ограничение</strong> — да ограничите обработката</li>
                <li><strong style="color:var(--white)">Право на преносимост</strong> — да получите данните в машинночетим формат</li>
                <li><strong style="color:var(--white)">Право на възражение</strong> — срещу определени видове обработка</li>
            </ul>

            <div class="legal-highlight">
                <p>
                    За упражняване на правата си изпратете имейл на
                    <strong style="color:var(--accent)">info@djstore.bg</strong>.
                    Ще отговорим в рамките на 30 дни.
                </p>
            </div>

            <!-- 8 -->
            <h2>8. Бисквитки (Cookies)</h2>
            <p>
                DJ Store използва бисквитки за функционирането на платформата:
            </p>
            <ul>
                <li><strong style="color:var(--white)">Сесийни бисквитки</strong> — за поддържане на вход и кошница</li>
                <li><strong style="color:var(--white)">Stripe бисквитки</strong> — за сигурно обработване на плащания</li>
            </ul>
            <p>
                Можете да управлявате бисквитките от настройките на браузъра си.
                Забраняването им може да повлияе на функционалността на сайта.
            </p>

            <!-- 9 -->
            <h2>9. Сигурност</h2>
            <p>
                Предприемаме технически и организационни мерки за защита на данните ви:
            </p>
            <ul>
                <li>SSL/TLS криптиране на всички комуникации</li>
                <li>Криптиране на пароли с bcrypt алгоритъм</li>
                <li>Плащанията се обработват от Stripe с PCI DSS сертификация</li>
                <li>Ограничен достъп до личните данни само за оторизиран персонал</li>
            </ul>

            <!-- 10 -->
            <h2>10. Промени в политиката</h2>
            <p>
                Запазваме правото да актуализираме тази политика. При съществени промени
                ще ви уведомим по имейл или чрез известие на сайта.
                Продължаването на използването на услугите след промяната означава
                приемане на новата политика.
            </p>

            <!-- Контакт -->
            <div class="legal-contact">
                <h3>📧 Свържи се с нас</h3>
                <p style="color:var(--grey-light);">За въпроси относно тази политика:</p>
                <p>📧 <strong style="color:var(--white)">info@djstore.bg</strong></p>
                <p>📞 <strong style="color:var(--white)">0800 123 456</strong></p>
                <p>🕒 <span style="color:var(--grey)">Пон–Пет: 9:00–18:00</span></p>
            </div>

        </div><!-- /legal-content -->
    </div>
</main>

<?php include $root . '/includes/footer.php'; ?>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>