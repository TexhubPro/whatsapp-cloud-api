# TexHub · WhatsApp Cloud API

[English](README.md) · **🌐 Русский**

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

Полноценный, не привязанный к фреймворку PHP SDK для **WhatsApp Cloud API** (WhatsApp Business Platform / Graph API) — отправка текста, медиа, шаблонов и **интерактивных кнопок и списков**, управление медиа, профилем и шаблонами, приём **вебхуков** — с полной поддержкой **Laravel**.

Документация: <https://developers.facebook.com/docs/whatsapp/cloud-api>

---

## ✨ Что покрыто

| Раздел | Методы |
|------|---------|
| **Сообщения** | текст, изображение, видео, аудио, документ, **интерактивные кнопки**, **списки**, **шаблоны**, локация, реакция, отметка прочтения |
| **Медиа** | загрузка, получить URL, **скачать**, удалить |
| **Профиль** | получить/обновить бизнес-профиль, номера телефонов |
| **Шаблоны** | список, создать, удалить |
| **Вебхуки** | проверка challenge, **проверка `X-Hub-Signature-256`**, разбор входящих сообщений + статусов доставки |
| **Запасной выход** | `->http()` для любого эндпоинта |

---

## 📦 Установка

```bash
composer require texhub/whatsapp-cloud-api
```

Требования: **PHP ≥ 8.2** с `curl`, `json`, `hash`.

---

## 🚀 Быстрый старт

```php
use TexHub\WhatsApp\WhatsApp;

$wa = WhatsApp::make(
    accessToken: 'YOUR_PERMANENT_TOKEN',
    phoneNumberId: 'YOUR_PHONE_NUMBER_ID',
    businessAccountId: 'YOUR_WABA_ID', // опционально, нужно для шаблонов/списка номеров
);

$wa->sendText('992900123456', 'Привет из TexHub! 👋');
```

> Номера — в международном формате **без** `+` (например `992900123456`).

---

## 💬 Сообщения

```php
use TexHub\WhatsApp\Builders\Button;

// Медиа (по публичному URL):
$wa->messages()->image('992900123456', 'https://cdn/photo.jpg', caption: 'Фото');
$wa->messages()->document('992900123456', 'https://cdn/file.pdf', filename: 'invoice.pdf');

// Интерактивные кнопки-ответы (макс. 3):
$wa->messages()->buttons('992900123456', 'Подтвердите заказ:', [
    Button::reply('confirm', 'Подтвердить'),
    Button::reply('cancel', 'Отменить'),
]);

// Интерактивный список:
$wa->messages()->list('992900123456', 'Выберите услугу', 'Открыть меню', [
    Button::section('Услуги', [
        Button::row('svc_1', 'Доставка', 'Курьером по городу'),
        Button::row('svc_2', 'Самовывоз'),
    ]),
]);

// Шаблон (единственный способ написать вне 24-часового окна):
$wa->messages()->template('992900123456', 'hello_world', 'en_US');

// Локация, реакция, отметка прочтения:
$wa->messages()->location('992900123456', 38.5598, 68.7870, 'Душанбе');
$wa->messages()->reaction('992900123456', 'wamid.XXX', '👍');
$wa->messages()->markRead('wamid.INCOMING');
```

---

## 📎 Медиа

```php
$id = $wa->media()->upload('/path/image.jpg')->id();   // загрузить, получить media id
$wa->messages()->mediaById('992900123456', 'image', $id);

$url = $wa->media()->url($mediaId);                     // временный URL для скачивания
$bytes = $wa->media()->download($mediaId);              // сырые байты
$wa->media()->delete($mediaId);
```

## 🪪 Профиль и шаблоны

```php
$wa->profile()->get();
$wa->profile()->update(['about' => 'TexHub — интеграции', 'email' => 'info@texhub.pro']);
$wa->profile()->phoneNumbers();      // нужен businessAccountId

$wa->templates()->list();            // нужен businessAccountId
```

---

## 🔔 Вебхуки

**Проверка (GET)** — вернуть challenge:

```php
$challenge = $wa->webhooks()->verifyChallenge($_GET);
if ($challenge !== null) { echo $challenge; exit; }
```

**События (POST)** — проверить подпись, затем разобрать сообщения и статусы:

```php
$raw = file_get_contents('php://input');
$wa->webhooks()->assertValidSignature($raw, $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? null);

foreach ($wa->webhooks()->parse($raw) as $event) {
    if ($event->isMessage()) {
        $from = $event->from();
        if ($event->messageType() === 'text') {
            $wa->messages()->markRead($event->messageId());
            $wa->messages()->text($from, 'Получили: ' . $event->text());
        }
        if ($event->messageType() === 'interactive') {
            $reply = $event->interactiveReply(); // ['id' => ..., 'title' => ...]
        }
    }

    if ($event->isStatus()) {
        $event->status(); // sent | delivered | read | failed
    }
}
http_response_code(200);
```

---

## 🧯 Обработка ошибок

```php
use TexHub\WhatsApp\Exceptions\ApiException;

try {
    $wa->sendText($to, $text);
} catch (ApiException $e) {
    $e->httpStatus; $e->errorCode; $e->errorType; $e->errorSubcode; $e->fbtraceId;
    $e->isTokenError();   // код 190
    $e->isRateLimit();
}
```

---

## <a name="laravel"></a>🧩 Laravel

Регистрируется автоматически. Опубликуйте конфиг:

```bash
php artisan vendor:publish --tag=whatsapp-config
```

`.env`:

```dotenv
WHATSAPP_ACCESS_TOKEN=...
WHATSAPP_PHONE_NUMBER_ID=...
WHATSAPP_BUSINESS_ACCOUNT_ID=...
WHATSAPP_APP_SECRET=...
WHATSAPP_WEBHOOK_VERIFY_TOKEN=...
WHATSAPP_API_VERSION=v23.0
```

Фасад:

```php
use TexHub\WhatsApp\Laravel\WhatsApp;

WhatsApp::sendText('992900123456', 'Привет из Laravel!');
WhatsApp::messages()->buttons('992900123456', 'Выбор:', [/* ... */]);
```

---

## 🏢 Multi-tenant / SaaS

Создан для SaaS, где много клиентов подключают **свои** WhatsApp. Одно приложение Meta, один webhook-URL, изоляция данных по арендатору.

```php
// 1) Онбординг клиента через Embedded Signup (фронт возвращает `code`):
$wa = WhatsApp::fromArray(['access_token' => '...', 'phone_number_id' => '...', 'app_id' => '...', 'app_secret' => '...']);
$token = $wa->onboarding()->exchangeCode($code)->get('access_token'); // бизнес-токен клиента
$wa->onboarding()->subscribeApp($wabaId, $token);                      // направить его вебхуки к вам
$wa->onboarding()->registerPhone($phoneNumberId, '123456', $token);    // 6-значный PIN
// → сохраните {token, waba_id, phone_number_id} для этого арендатора

// 2) Отправка от любого арендатора — клиент с его сохранёнными кредами:
WhatsApp::fromArray($tenant->whatsappConfig())->sendText($to, $text);

// 3) Один вебхук на всех — роутинг по номеру / WABA id:
foreach ($wa->webhooks()->parse($raw) as $event) {
    $tenant = Tenant::where('wa_phone_number_id', $event->phoneNumberId())->first();
    //      или ->where('wa_business_account_id', $event->wabaId())
}
```

`$event->phoneNumberId()`, `->wabaId()`, `->displayPhoneNumber()`, `->contactName()` дают всё нужное для маршрутизации события нужному клиенту. Подпись вебхука проверяется одним общим app secret.

## 🧪 Тестирование

```php
use TexHub\WhatsApp\WhatsApp;
use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\Tests\Support\FakeTransport;

$t = (new FakeTransport())->push(['messages' => [['id' => 'wamid.1']]]);
$wa = new WhatsApp(new Config('TOKEN', '123456'), $t);
$wa->sendText('992900123456', 'hi'); // проверяйте $t->last()
```

```bash
composer install && composer test
```

---

## 📚 Архитектура

```
src/
├── WhatsApp.php             # точка входа — messages()/media()/profile()/templates()/webhooks()
├── Config.php               # неизменяемая конфигурация
├── Http/                    # Transport, CurlTransport (JSON/multipart), HttpClient, FileParam
├── Builders/                # Message, Button (интерактивные кнопки и списки)
├── Resources/               # Messages, Media, Profile, Templates
├── Webhook/                 # WebhookHandler (challenge + подпись + разбор), WebhookEvent
├── Responses/               # Response (ArrayAccess), ListResponse
├── Exceptions/              # ApiException, TransportException, …
└── Laravel/                 # ServiceProvider + Facade
```

---

## Лицензия

MIT © TexHub Pro — разработано Mahmudi Shodmehr.
