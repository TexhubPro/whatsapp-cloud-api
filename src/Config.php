<?php

declare(strict_types=1);

namespace TexHub\WhatsApp;

use TexHub\WhatsApp\Exceptions\ConfigurationException;

/**
 * Immutable SDK configuration for the WhatsApp Cloud API.
 */
final class Config
{
    public const DEFAULT_GRAPH_URL = 'https://graph.facebook.com';
    public const DEFAULT_VERSION = 'v23.0';

    /**
     * @param string      $accessToken         System-user / permanent access token (Bearer).
     * @param string      $phoneNumberId       The WhatsApp phone number id used to send messages.
     * @param string|null $businessAccountId   WABA id (for templates, phone numbers).
     * @param string|null $appSecret           App secret used to verify webhook signatures.
     * @param string|null $webhookVerifyToken  Token to validate webhook subscription challenges.
     * @param string      $graphUrl            Graph API base URL.
     * @param string      $version             API version.
     * @param int         $timeout             HTTP timeout in seconds.
     */
    public function __construct(
        public readonly string $accessToken,
        public readonly string $phoneNumberId,
        public readonly ?string $businessAccountId = null,
        public readonly ?string $appSecret = null,
        public readonly ?string $webhookVerifyToken = null,
        public readonly string $graphUrl = self::DEFAULT_GRAPH_URL,
        public readonly string $version = self::DEFAULT_VERSION,
        public readonly int $timeout = 30,
    ) {
        if (trim($this->accessToken) === '') {
            throw new ConfigurationException('WhatsApp access token must not be empty.');
        }

        if (trim($this->phoneNumberId) === '') {
            throw new ConfigurationException('WhatsApp phone number id must not be empty.');
        }

        if ($this->timeout < 1) {
            throw new ConfigurationException('WhatsApp timeout must be a positive number of seconds.');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            accessToken: (string) ($config['access_token'] ?? ''),
            phoneNumberId: (string) ($config['phone_number_id'] ?? ''),
            businessAccountId: self::nullableString($config['business_account_id'] ?? null),
            appSecret: self::nullableString($config['app_secret'] ?? null),
            webhookVerifyToken: self::nullableString($config['webhook_verify_token'] ?? null),
            graphUrl: (string) ($config['graph_url'] ?? self::DEFAULT_GRAPH_URL),
            version: (string) ($config['version'] ?? self::DEFAULT_VERSION),
            timeout: (int) ($config['timeout'] ?? 30),
        );
    }

    /**
     * Build a versioned Graph API URL for a path.
     */
    public function url(string $path): string
    {
        $path = ltrim($path, '/');
        $base = rtrim($this->graphUrl, '/');

        if ($this->version !== '' && ! str_starts_with($path, $this->version)) {
            $base .= '/' . $this->version;
        }

        return $base . '/' . $path;
    }

    public function requireBusinessAccountId(): string
    {
        if ($this->businessAccountId === null || trim($this->businessAccountId) === '') {
            throw new ConfigurationException('A WhatsApp Business Account id (business_account_id) is required for this call.');
        }

        return $this->businessAccountId;
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
