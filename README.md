# TexHub · WhatsApp Cloud API

**🌐 English** · [Русский](README.ru.md)

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

A complete, framework-agnostic PHP SDK for the **WhatsApp Cloud API** (WhatsApp Business Platform / Graph API) — send text, media, templates and **interactive buttons & lists**, manage media, business profile and templates, and receive **webhooks** — with first-class **Laravel** support.

Reference: <https://developers.facebook.com/docs/whatsapp/cloud-api>

---

## ✨ What's covered

| Area | Methods |
|------|---------|
| **Messages** | text, image, video, audio, document, **interactive buttons**, **lists**, **templates**, location, reaction, mark-read |
| **Media** | upload, get URL, **download**, delete |
| **Profile** | get/update business profile, phone numbers |
| **Templates** | list, create, delete |
| **Webhooks** | verify challenge, **verify `X-Hub-Signature-256`**, parse incoming messages + delivery statuses |
| **Escape hatch** | `->http()` for any endpoint |

---

## 📦 Installation

```bash
composer require texhub/whatsapp-cloud-api
```

Requirements: **PHP ≥ 8.2** with `curl`, `json`, `hash`.

---

## 🚀 Quick start

```php
use TexHub\WhatsApp\WhatsApp;

$wa = WhatsApp::make(
    accessToken: 'YOUR_PERMANENT_TOKEN',
    phoneNumberId: 'YOUR_PHONE_NUMBER_ID',
    businessAccountId: 'YOUR_WABA_ID', // optional, needed for templates/phone list
);

$wa->sendText('992900123456', 'Привет из TexHub! 👋');
```

> Numbers are in international format **without** `+` (e.g. `992900123456`).

---

## 💬 Messages

```php
use TexHub\WhatsApp\Builders\Button;

// Media (by public URL):
$wa->messages()->image('992900123456', 'https://cdn/photo.jpg', caption: 'Фото');
$wa->messages()->document('992900123456', 'https://cdn/file.pdf', filename: 'invoice.pdf');

// Interactive reply buttons (max 3):
$wa->messages()->buttons('992900123456', 'Подтвердите заказ:', [
    Button::reply('confirm', 'Подтвердить'),
    Button::reply('cancel', 'Отменить'),
]);

// Interactive list:
$wa->messages()->list('992900123456', 'Выберите услугу', 'Открыть меню', [
    Button::section('Услуги', [
        Button::row('svc_1', 'Доставка', 'Курьером по городу'),
        Button::row('svc_2', 'Самовывоз'),
    ]),
]);

// Template (the only way to message outside the 24h window):
$wa->messages()->template('992900123456', 'hello_world', 'en_US');

// Location, reaction, mark as read:
$wa->messages()->location('992900123456', 38.5598, 68.7870, 'Душанбе');
$wa->messages()->reaction('992900123456', 'wamid.XXX', '👍');
$wa->messages()->markRead('wamid.INCOMING');
```

---

## 📎 Media

```php
$id = $wa->media()->upload('/path/image.jpg')->id();   // upload, get a media id
$wa->messages()->mediaById('992900123456', 'image', $id);

$url = $wa->media()->url($mediaId);                     // temporary download URL
$bytes = $wa->media()->download($mediaId);              // raw binary
$wa->media()->delete($mediaId);
```

## 🪪 Profile & templates

```php
$wa->profile()->get();
$wa->profile()->update(['about' => 'TexHub — интеграции', 'email' => 'info@texhub.pro']);
$wa->profile()->phoneNumbers();      // needs businessAccountId

$wa->templates()->list();            // needs businessAccountId
```

---

## 🔔 Webhooks

**Verification (GET)** — echo the challenge:

```php
$challenge = $wa->webhooks()->verifyChallenge($_GET);
if ($challenge !== null) { echo $challenge; exit; }
```

**Events (POST)** — verify the signature, then parse messages & statuses:

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

## 🧯 Error handling

```php
use TexHub\WhatsApp\Exceptions\ApiException;

try {
    $wa->sendText($to, $text);
} catch (ApiException $e) {
    $e->httpStatus; $e->errorCode; $e->errorType; $e->errorSubcode; $e->fbtraceId;
    $e->isTokenError();   // code 190
    $e->isRateLimit();
}
```

---

## <a name="laravel"></a>🧩 Laravel

Auto-discovered. Publish config:

```bash
php artisan vendor:publish --tag=whatsapp-config
```

`.env`:

```dotenv
WHATSAPP_ACCESS_TOKEN=...
WHATSAPP_PHONE_NUMBER_ID=...
WHATSAPP_BUSINESS_ACCOUNT_ID=...
WHATSAPP_APP_ID=...
WHATSAPP_APP_SECRET=...
WHATSAPP_WEBHOOK_VERIFY_TOKEN=...
WHATSAPP_API_VERSION=v23.0
```

Facade:

```php
use TexHub\WhatsApp\Laravel\WhatsApp;

WhatsApp::sendText('992900123456', 'Привет из Laravel!');
WhatsApp::messages()->buttons('992900123456', 'Выбор:', [/* ... */]);
```

---

## 🏢 Multi-tenant / SaaS

Built for SaaS where many customers connect **their own** WhatsApp. One Meta app, one webhook URL, isolated per-tenant data.

```php
// 1) Customer onboarding via Embedded Signup (front-end returns a `code`):
$wa = WhatsApp::fromArray(['access_token' => '...', 'phone_number_id' => '...', 'app_id' => '...', 'app_secret' => '...']);
$token = $wa->onboarding()->exchangeCode($code)->get('access_token'); // customer business token
$wa->onboarding()->subscribeApp($wabaId, $token);                      // route their webhooks to you
$wa->onboarding()->registerPhone($phoneNumberId, '123456', $token);    // 6-digit PIN
// → store {token, waba_id, phone_number_id} for this tenant

// 2) Send as any tenant — build a client with their stored creds:
WhatsApp::fromArray($tenant->whatsappConfig())->sendText($to, $text);

// 3) One webhook for everyone — route by phone number / WABA id:
foreach ($wa->webhooks()->parse($raw) as $event) {
    $tenant = Tenant::where('wa_phone_number_id', $event->phoneNumberId())->first();
    //      or ->where('wa_business_account_id', $event->wabaId())
}
```

`$event->phoneNumberId()`, `->wabaId()`, `->displayPhoneNumber()`, `->contactName()` give you everything needed to route to the right customer. Webhook signatures are verified with your single app secret.

## 🧪 Testing

```php
use TexHub\WhatsApp\WhatsApp;
use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\Tests\Support\FakeTransport;

$t = (new FakeTransport())->push(['messages' => [['id' => 'wamid.1']]]);
$wa = new WhatsApp(new Config('TOKEN', '123456'), $t);
$wa->sendText('992900123456', 'hi'); // assert on $t->last()
```

```bash
composer install && composer test
```

---

## 📚 Architecture

```
src/
├── WhatsApp.php             # entry — messages()/media()/profile()/templates()/webhooks()
├── Config.php               # immutable configuration
├── Http/                    # Transport, CurlTransport (JSON/multipart), HttpClient, FileParam
├── Builders/                # Message, Button (interactive buttons & lists)
├── Resources/               # Messages, Media, Profile, Templates
├── Webhook/                 # WebhookHandler (challenge + signature + parse), WebhookEvent
├── Responses/               # Response (ArrayAccess), ListResponse
├── Exceptions/              # ApiException, TransportException, …
└── Laravel/                 # ServiceProvider + Facade
```

---

## License

MIT © TexHub Pro — built by Mahmudi Shodmehr.
