<?php
// pages/terms.php
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
    <title>Условия за ползване — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        html, body { background: #0a0a0a !important; color: #f5f5f0 !important; min-height: 100vh; }
        .legal-page { padding: 60px 0 100px; }
        .legal-header { padding: 60px 0; border-bottom: 1px solid var(--dark-4); margin-bottom: 60px; }
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
        .legal-title { font-size: clamp(2.5rem, 5vw, 5rem); color: var(--white); margin-bottom: 16px; }
        .legal-updated { color: var(--grey); font-size: .9rem; }
        .legal-content { max-width: 800px; }
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
        .legal-content ul { list-style: none; margin-bottom: 16px; }
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
        .legal-content ul li:last-child { border-bottom: none; }
        .legal-highlight {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-left: 3px solid var(--accent);
            border-radius: var(--radius);
            padding: 20px 24px;
            margin: 24px 0;
        }
        .legal-highlight p { margin-bottom: 0; color: var(--grey-light); }
        .legal-contact {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: var(--radius-lg);
            padding: 32px;
            margin-top: 48px;
        }
        .legal-contact h3 { color: var(--white) !important; font-size: 1.4rem !important; margin-top: 0 !important; }
        .legal-contact p { margin-bottom: 8px; }
    </style>
</head>
<body>
<?php include $root . '/includes/header.php'; ?>

<div class="legal-header">
    <div class="container">
        <span class="legal-label">Правна информация</span>
        <h1 class="legal-title">УСЛОВИЯ ЗА<br>ПОЛЗВАНЕ</h1>
        <p class="legal-updated">Последна актуализация: <?= date('d.m.Y') ?></p>
    </div>
</div>

<main class="legal-page">
    <div class="container">
        <div class="legal-content">

            <div class="legal-highlight">
                <p>
                    Моля, прочетете внимателно тези условия преди да използвате DJ Store.
                    С достъпа до сайта и използването на услугите ни вие приемате тези условия.
                </p>
            </div>

            <!-- 1 -->
            <h2>1. Обща информация</h2>
            <p>
                DJ Store е онлайн магазин за професионална DJ техника, достъпен на адрес
                <strong style="color:var(--accent)"><?= SITE_URL ?></strong>.
                Платформата предоставя възможност за разглеждане и закупуване на DJ оборудване.
            </p>
            <p>
                Тези Общи условия уреждат отношенията между DJ Store и всеки потребител,
                който използва услугите на платформата.
            </p>

            <!-- 2 -->
            <h2>2. Регистрация и акаунт</h2>

            <h3>2.1 Създаване на акаунт</h3>
            <p>
                За да направите поръчка, трябва да създадете акаунт. При регистрацията
                се задължавате да предоставите вярна и актуална информация.
            </p>
            <ul>
                <li>Трябва да сте навършили 18 години</li>
                <li>Данните трябва да са верни и актуални</li>
                <li>Един имейл адрес може да се използва само за един акаунт</li>
                <li>Отговорни сте за сигурността на паролата си</li>
            </ul>

            <h3>2.2 Сигурност на акаунта</h3>
            <p>
                Вие сте отговорни за всички действия, извършени чрез вашия акаунт.
                При съмнение за неоторизиран достъп незабавно ни уведомете на
                <strong style="color:var(--white)">info@djstore.bg</strong>.
            </p>

            <!-- 3 -->
            <h2>3. Продукти и цени</h2>

            <h3>3.1 Описания на продукти</h3>
            <p>
                Полагаме усилия описанията и снимките на продуктите да са точни и актуални.
                Въпреки това не гарантираме пълна точност на всички характеристики.
            </p>

            <h3>3.2 Цени</h3>
            <ul>
                <li>Всички цени са в евро (€) и включват ДДС</li>
                <li>Запазваме правото да променяме цените по всяко време</li>
                <li>Цената при потвърждение на поръчката е окончателна</li>
                <li>Промо кодовете са валидни само в рамките на посочения период</li>
            </ul>

            <h3>3.3 Наличност</h3>
            <p>
                Всички продукти се предлагат до изчерпване на наличностите.
                При липса на наличност ще се свържем с вас за алтернативно решение.
            </p>

            <!-- 4 -->
            <h2>4. Поръчки и плащане</h2>

            <h3>4.1 Процес на поръчка</h3>
            <ul>
                <li>Добавете продукти в кошницата</li>
                <li>Попълнете данните за доставка</li>
                <li>Изберете начин на плащане</li>
                <li>Потвърдете поръчката</li>
            </ul>

            <h3>4.2 Начини на плащане</h3>
            <ul>
                <li><strong style="color:var(--white)">Банкова карта</strong> — Visa, Mastercard, American Express чрез Stripe</li>
                <li><strong style="color:var(--white)">Наложен платеж</strong> — плащане в брой при доставка</li>
            </ul>

            <h3>4.3 Потвърждение на поръчка</h3>
            <p>
                След успешна поръчка получавате потвърждение. DJ Store си запазва правото
                да откаже поръчка при констатирана грешка в цената или наличността.
            </p>

            <!-- 5 -->
            <h2>5. Доставка</h2>

            <h3>5.1 Срокове</h3>
            <ul>
                <li>Стандартна доставка: 2-5 работни дни</li>
                <li>Доставката се извършва чрез Speedy и Econt</li>
                <li>Работни дни: понеделник — петък</li>
            </ul>

            <h3>5.2 Цена на доставката</h3>
            <ul>
                <li>Безплатна доставка при поръчки над €200</li>
                <li>При поръчки под €200 — €8.99 за доставка</li>
            </ul>

            <h3>5.3 Адрес за доставка</h3>
            <p>
                Отговорността за посочването на верен адрес е на клиента.
                DJ Store не носи отговорност за забавяния или недоставяне
                поради грешен адрес.
            </p>

            <!-- 6 -->
            <h2>6. Връщане и рекламации</h2>

            <h3>6.1 Право на отказ</h3>
            <p>
                Имате право да върнете продукт в рамките на <strong style="color:var(--white)">14 дни</strong>
                от получаването без да посочвате причина, при условие че:
            </p>
            <ul>
                <li>Продуктът е в оригинална опаковка и ненарушен вид</li>
                <li>Не е използван и няма следи от употреба</li>
                <li>Придружен е с оригинален касов бон или фактура</li>
            </ul>

            <h3>6.2 Процедура за връщане</h3>
            <p>
                За да върнете продукт, свържете се с нас на
                <strong style="color:var(--white)">info@djstore.bg</strong>
                с номера на поръчката и причината за връщане.
                Разходите по връщането са за сметка на клиента.
            </p>

            <h3>6.3 Гаранция</h3>
            <p>
                Всички продукти се предлагат с минимум
                <strong style="color:var(--white)">2 години гаранция</strong>
                съгласно Закона за защита на потребителите.
                Гаранционните условия са специфични за всеки производител.
            </p>

            <!-- 7 -->
            <h2>7. Промо кодове</h2>
            <ul>
                <li>Промо кодовете са еднократни освен ако не е посочено друго</li>
                <li>Не могат да се комбинират с други промоции</li>
                <li>Валидни са само в рамките на посочения период</li>
                <li>DJ Store си запазва правото да анулира промо код при злоупотреба</li>
                <li>Нямат парична стойност и не могат да се обменят за пари</li>
            </ul>

            <!-- 8 -->
            <h2>8. Интелектуална собственост</h2>
            <p>
                Всички материали на DJ Store — текстове, изображения, лога, дизайн —
                са защитени от авторско право. Забранено е копирането, разпространението
                или използването им без писмено разрешение.
            </p>

            <!-- 9 -->
            <h2>9. Ограничение на отговорността</h2>
            <p>
                DJ Store не носи отговорност за:
            </p>
            <ul>
                <li>Непреки или последващи вреди от използването на платформата</li>
                <li>Технически прекъсвания или недостъпност на сайта</li>
                <li>Действия на трети страни (куриери, платежни доставчици)</li>
                <li>Несъвместимост на продуктите с конкретно оборудване на клиента</li>
            </ul>

            <!-- 10 -->
            <h2>10. Приложимо право</h2>
            <p>
                Тези условия се уреждат от законодателството на
                <strong style="color:var(--white)">Република България</strong>.
                Всички спорове се решават по взаимно съгласие или пред компетентен
                български съд.
            </p>
            <p>
                Потребителите в ЕС могат да използват платформата за онлайн решаване
                на спорове на
                <strong style="color:var(--white)">ec.europa.eu/consumers/odr</strong>.
            </p>

            <!-- 11 -->
            <h2>11. Промени в условията</h2>
            <p>
                DJ Store си запазва правото да актуализира тези условия по всяко време.
                Промените влизат в сила веднага след публикуването им.
                Продължаването на използването на платформата означава приемане на
                актуализираните условия.
            </p>

            <!-- Контакт -->
            <div class="legal-contact">
                <h3>📧 Въпроси?</h3>
                <p style="color:var(--grey-light);">
                    Ако имате въпроси относно тези условия:
                </p>
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