<?php

declare(strict_types=1);

namespace TexHub\WhatsApp;

use TexHub\WhatsApp\Http\CurlTransport;
use TexHub\WhatsApp\Http\HttpClient;
use TexHub\WhatsApp\Http\Transport;
use TexHub\WhatsApp\Resources\MediaClient;
use TexHub\WhatsApp\Resources\MessagesClient;
use TexHub\WhatsApp\Resources\ProfileClient;
use TexHub\WhatsApp\Resources\TemplatesClient;
use TexHub\WhatsApp\Responses\Response;
use TexHub\WhatsApp\Webhook\WebhookHandler;

/**
 * Entry point of the WhatsApp Cloud API SDK.
 *
 * Framework-agnostic: construct it directly, or resolve it from the container
 * in Laravel via the {@see \TexHub\WhatsApp\Laravel\WhatsApp} facade.
 *
 * ```php
 * $wa = WhatsApp::make('ACCESS_TOKEN', 'PHONE_NUMBER_ID');
 * $wa->sendText('992900123456', 'Привет из TexHub!');
 * ```
 */
final class WhatsApp
{
    private readonly Transport $transport;
    private readonly HttpClient $httpClient;

    /** @var array<string, object> */
    private array $resources = [];

    public function __construct(
        private readonly Config $config,
        ?Transport $transport = null,
    ) {
        $this->transport = $transport ?? new CurlTransport($config->timeout);
        $this->httpClient = new HttpClient($config, $this->transport);
    }

    public static function make(
        string $accessToken,
        string $phoneNumberId,
        ?string $businessAccountId = null,
        ?Transport $transport = null,
    ): self {
        return new self(new Config($accessToken, $phoneNumberId, $businessAccountId), $transport);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config, ?Transport $transport = null): self
    {
        return new self(Config::fromArray($config), $transport);
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function http(): HttpClient
    {
        return $this->httpClient;
    }

    public function messages(): MessagesClient
    {
        return $this->resource(MessagesClient::class);
    }

    public function media(): MediaClient
    {
        return $this->resource(MediaClient::class);
    }

    public function profile(): ProfileClient
    {
        return $this->resource(ProfileClient::class);
    }

    public function templates(): TemplatesClient
    {
        return $this->resource(TemplatesClient::class);
    }

    public function webhooks(): WebhookHandler
    {
        return $this->resources[WebhookHandler::class] ??= new WebhookHandler($this->config);
    }

    /**
     * Shortcut: send a plain text message.
     */
    public function sendText(string $to, string $body): Response
    {
        return $this->messages()->text($to, $body);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function resource(string $class): object
    {
        /** @var T */
        return $this->resources[$class] ??= new $class($this->httpClient, $this->config);
    }
}
