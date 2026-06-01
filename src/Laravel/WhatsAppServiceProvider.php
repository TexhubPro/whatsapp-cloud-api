<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\WhatsApp as WhatsAppClient;

class WhatsAppServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/whatsapp.php', 'whatsapp');

        $this->app->singleton(Config::class, function ($app): Config {
            return Config::fromArray((array) $app['config']->get('whatsapp', []));
        });

        $this->app->singleton(WhatsAppClient::class, function ($app): WhatsAppClient {
            return new WhatsAppClient($app->make(Config::class));
        });

        $this->app->alias(WhatsAppClient::class, 'whatsapp');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/whatsapp.php' => $this->app->configPath('whatsapp.php'),
            ], 'whatsapp-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Config::class, WhatsAppClient::class, 'whatsapp'];
    }
}
