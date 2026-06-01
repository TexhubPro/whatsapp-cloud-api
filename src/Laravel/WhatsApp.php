<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for the WhatsApp Cloud API client.
 *
 * @method static \TexHub\WhatsApp\Resources\MessagesClient  messages()
 * @method static \TexHub\WhatsApp\Resources\MediaClient      media()
 * @method static \TexHub\WhatsApp\Resources\ProfileClient    profile()
 * @method static \TexHub\WhatsApp\Resources\TemplatesClient  templates()
 * @method static \TexHub\WhatsApp\Resources\OnboardingClient onboarding()
 * @method static \TexHub\WhatsApp\Webhook\WebhookHandler     webhooks()
 * @method static \TexHub\WhatsApp\Responses\Response          sendText(string $to, string $body)
 * @method static \TexHub\WhatsApp\Http\HttpClient            http()
 * @method static \TexHub\WhatsApp\Config                     config()
 *
 * @see \TexHub\WhatsApp\WhatsApp
 */
class WhatsApp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'whatsapp';
    }
}
