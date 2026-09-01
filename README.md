<div align="center">

# Commerce Order Mail

**Drupal Commerce > Email via configurable SMTP** · Обход ограничения Яндекса с 29.06.2026 · Gmail / любой SMTP · Шаблоны с токенами

[![Latest Release](https://img.shields.io/github/v/release/Mmitekk/commerce_order_mail?sort=semver&label=latest)](https://github.com/Mmitekk/commerce_order_mail/releases) [![Drupal](https://img.shields.io/badge/Drupal-10%20%7C%2011-blue.svg)](https://drupal.org) [![Commerce](https://img.shields.io/badge/Commerce-2.x-orange.svg)](https://drupalcommerce.org) [![License: GPL v2](https://img.shields.io/badge/License-GPL--2.0-lightgrey.svg)](LICENSE)

[???? Русский](#-русский) · [???? English](#-english) · [Установка](#-установка--installation) · [Настройка Gmail](#-настройка-gmail--gmail-setup) · [Токены](#-токены--tokens)

</div>

<style>
.tabs{margin:16px 0}
.tabs input{display:none}
.tabs label{display:inline-block;padding:8px 16px;cursor:pointer;border:1px solid #d0d7de;border-bottom:none;border-radius:6px 6px 0 0;background:#f6f8fa;margin-right:4px;font-weight:600}
.tabs input:checked+label{background:#fff;border-bottom:1px solid #fff;margin-bottom:-1px}
.tab-content{display:none;border:1px solid #d0d7de;border-radius:0 6px 6px 6px;padding:16px;background:#fff}
#tab-ru:checked ~ #content-ru,#tab-en:checked ~ #content-en{display:block}
</style>

<div class="tabs">
<input type="radio" name="tabs" id="tab-ru" checked><label for="tab-ru">???? Русский</label>
<input type="radio" name="tabs" id="tab-en"><label for="tab-en">???? English</label>

<div id="content-ru" class="tab-content">

## ???? Русский

### Проблема
С **29 июня 2026 года Яндекс отключил бесплатный доступ к почте по SMTP/IMAP/POP3** для доменов на Яндекс Почте. Отправка с сайта через `smtp.yandex.ru` теперь работает только на **платном тарифе Яндекс 360**. Если почта делегирована на Яндекс, стандартный `mail()` / SMTP Яндекса перестаёт работать.

### Решение
**Commerce Order Mail** — модуль Drupal 10/11 для **доставки сабмитов заказов Drupal Commerce на email через любой SMTP**, который вы укажете в админке.

- В админке (`/admin/commerce/config/order-mail`) указываете **авторизационные данные любого ящика**: Google-аккаунт (Gmail), Mail.ru, корпоративный SMTP, SendGrid, Brevo и т.д.
- Поля для **IMAP/SMTP настроек**: host, port, encryption (none/TLS/SSL), username, password/App Password, таймаут, allow self-signed.
- **Шаблон темы и тела письма с токенами Commerce для Drupal 10** (использует `token`): `[commerce_order:order_number]`, `[commerce_order:total_price]`, `[commerce_order:mail]`, `[commerce_order:order_items_table]` и т.д.
- Автоматическая отправка при переходе заказа в состояние `place`/`validate` (настраивается) + кнопка **«Отправить тестовое письмо»**.
- Прямая отправка через `Symfony\Component\Mailer` (EsmtpTransport) — **минует Яндекс**. Если SMTP не настроен — fallback на стандартный `mail()` Drupal.

> Ищете ли вы модуль для этого? Да — это он. Если не находили — теперь он есть.

### Возможности
- Включение/выключение, список получателей через запятую
- Отправитель (email + имя)
- SMTP: host, port, шифрование, логин, пароль (хранится в config, не показывается повторно), таймаут
- Шаблоны: тема (textfield) + тело (textarea, HTML/plain text)
- Токены: все токены `commerce_order` + два дополнительных от модуля: `[commerce_order:order_items_table]` (HTML-таблица), `[commerce_order:order_items_text]` (plain text)
- Триггеры: draft/validate/place/fulfillment/completed (чекбоксы)
- Копия покупателю (опционально), логирование успеха

### Требования
- Drupal `^10 || ^11`
- Commerce `^2.0` (`commerce_order`)
- Token `^1.0` (рекомендуется)

### Быстрый старт
1. Установите (см. [Установка](#-установка--installation) ниже — без токена, берётся последний релиз).
2. Включите: `drush en commerce_order_mail` или через `/admin/modules`.
3. Откройте `/admin/commerce/config/order-mail`, заполните получателей, отправителя и SMTP Gmail/другого сервиса, сохраните.
4. Нажмите «Отправить тестовое письмо», проверьте входящие.
5. Оформите тестовый заказ — проверьте письмо.

</div>

<div id="content-en" class="tab-content">

## ???? English

### Problem
Since **June 29, 2026 Yandex disabled free SMTP/IMAP/POP3** for domains delegated to Yandex Mail. Sending from a site via `smtp.yandex.ru` now requires a **paid Yandex 360 plan**. If your mailbox is delegated to Yandex, the standard site mail breaks.

### Solution
**Commerce Order Mail** is a Drupal 10/11 module that delivers **Drupal Commerce order submissions to email via any SMTP** you configure in the admin UI.

- At `/admin/commerce/config/order-mail` set **credentials of any mailbox**: Google account (Gmail), Mail.ru, corporate SMTP, SendGrid, Brevo, etc.
- Fields for **IMAP/SMTP settings**: host, port, encryption (none/TLS/SSL), username, password/App Password, timeout, allow self-signed.
- **Subject & body templates with Drupal 10 Commerce tokens** (via `token`): `[commerce_order:order_number]`, `[commerce_order:total_price]`, `[commerce_order:mail]`, `[commerce_order:order_items_table]`, etc.
- Auto-send on order transition to `place`/`validate` (configurable) + **Send test email** button.
- Direct send via `Symfony\Component\Mailer` (EsmtpTransport) — **bypasses Yandex**. Falls back to Drupal default mail if SMTP not configured.

### Features
- Enable/disable, comma-separated recipients
- Sender email + name
- SMTP: host, port, encryption, username, password (stored in config, not shown again), timeout
- Templates: subject (textfield) + body (textarea, HTML/plain)
- Tokens: all `commerce_order` tokens + two extra: `[commerce_order:order_items_table]` (HTML table), `[commerce_order:order_items_text]` (plain)
- Triggers: draft/validate/place/fulfillment/completed (checkboxes)
- Copy to customer (optional), success logging

### Requirements
- Drupal `^10 || ^11`
- Commerce `^2.0`
- Token `^1.0` (recommended)

### Quick start
1. Install (see [Installation](#-установка--installation) below — no token, latest release).
2. Enable: `drush en commerce_order_mail`.
3. Go to `/admin/commerce/config/order-mail`, fill recipients, sender and SMTP (Gmail/other), save.
4. Click “Send test email”.
5. Place a test order.

</div>
</div>

---

## ?? Установка / Installation

> **Без токена и без `dev-main`. Модуль ставится с последнего GitHub Release.**

### Вариант A — стабильный релиз (рекомендуется)

```bash
composer config repositories.commerce_order_mail vcs https://github.com/Mmitekk/commerce_order_mail
composer require mmitekk/commerce_order_mail:^1.0
drush en commerce_order_mail -y
```

> `^1.0` автоматически берёт **последний билд (latest release)**. Никаких `dev-main`/`dev-master`. Репозиторий публичный — токен не нужен.

### Вариант B — напрямую с релиза (без composer repositories)

```bash
composer require mmitekk/commerce_order_mail
```
Если пакет ещё не на Packagist, используйте Вариант A (vcs).

### Обновление

```bash
composer update mmitekk/commerce_order_mail
drush updb -y && drush cr
```

### Ручная установка

Скачайте zip последнего релиза: https://github.com/Mmitekk/commerce_order_mail/releases/latest > распакуйте в `modules/contrib/commerce_order_mail`.

---

## ?? Настройка Gmail / Gmail setup

| Поле | Значение |
|------|----------|
| SMTP host | `smtp.gmail.com` |
| Port | `587` + TLS **или** `465` + SSL |
| Username | `your@gmail.com` (полный адрес Google-аккаунта) |
| Password | **App Password** (16 символов) |

Создание App Password: `myaccount.google.com` > **Безопасность** > **Двухэтапная аутентификация** (включить) > **Пароли приложений** > создайте для «Почта» > скопируйте 16-символьный пароль в поле SMTP password модуля.

IMAP (если нужен для чтения, для отправки не требуется): `imap.gmail.com:993` SSL.

Для Mail.ru: `smtp.mail.ru:465` SSL. Для корпоративного — уточните у админа. Для SendGrid/Brevo — их SMTP host/port/key.

---

## ?? Токены / Tokens

Используются стандартные токены Commerce для Drupal 10 + два от модуля:

```
[commerce_order:order_number]      — номер заказа
[commerce_order:total_price]       — сумма
[commerce_order:mail]              — email покупателя
[commerce_order:created]           — дата создания
[commerce_order:state]             — состояние
[commerce_order:order_items]       — товары (дефолт)
[commerce_order:order_items_table] — ? HTML-таблица товаров (модуль)
[commerce_order:order_items_text]  — ? plain-text список товаров (модуль)
[commerce_order:billing_profile]   — профиль плательщика
[commerce_order:shipping_profile]  — доставка
[site:name] [site:url] [user:display-name] ...
```

Полный список: `/admin/help/token` (модуль Token > Browse available tokens).

**Пример темы:** `Новый заказ [commerce_order:order_number] на [site:name]`
**Пример тела:** см. дефолтный шаблон в настройках (HTML с таблицей товаров).

---

## ?? Troubleshooting

- **Письмо не приходит** > проверьте `/admin/reports/dblog` (канал `commerce_order_mail`), проверьте получателей (запятая, без пробелов в конце), проверьте SMTP App Password, попробуйте порт 465/SSL.
- **Yandex** > не используйте `smtp.yandex.ru` без Яндекс 360. Переключите на Gmail/другой SMTP.
- **Токены не заменяются** > включите модуль `token`, очистите кэш `drush cr`.
- **HTML уезжает** > выберите `text/html` в формате тела, проверьте шаблон.

---

## ?? License

GPL-2.0-or-later. См. [LICENSE](LICENSE).

## ?? Issues

https://github.com/Mmitekk/commerce_order_mail/issues

---

<div align="center"><sub>Сделано для обхода ограничения Яндекса 29.06.2026 · Made to bypass Yandex restriction 2026-06-29</sub></div>
