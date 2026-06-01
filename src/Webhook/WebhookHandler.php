<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Webhook;

use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\Exceptions\InvalidSignatureException;
use TexHub\WhatsApp\Exceptions\WhatsAppException;

/**
 * Verifies and parses WhatsApp Cloud API webhooks.
 *
 * - Subscription verification: echo `hub.challenge` when `hub.verify_token` matches.
 * - Event delivery: verify `X-Hub-Signature-256` (HMAC-SHA256 of the raw body
 *   with the app secret), then parse messages and status updates.
 */
final class WebhookHandler
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    /**
     * Handle the GET subscription handshake. Returns the challenge to echo, or null.
     *
     * @param array<string, mixed> $query
     */
    public function verifyChallenge(array $query): ?string
    {
        $mode = $query['hub_mode'] ?? $query['hub.mode'] ?? null;
        $token = $query['hub_verify_token'] ?? $query['hub.verify_token'] ?? null;
        $challenge = $query['hub_challenge'] ?? $query['hub.challenge'] ?? null;

        if ($mode === 'subscribe'
            && $this->config->webhookVerifyToken !== null
            && hash_equals($this->config->webhookVerifyToken, (string) $token)) {
            return $challenge === null ? null : (string) $challenge;
        }

        return null;
    }

    public function verifySignature(string $rawBody, ?string $signatureHeader): bool
    {
        if ($signatureHeader === null || ! str_starts_with($signatureHeader, 'sha256=') || $this->config->appSecret === null) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $this->config->appSecret);

        return hash_equals($expected, $signatureHeader);
    }

    /**
     * @throws InvalidSignatureException
     */
    public function assertValidSignature(string $rawBody, ?string $signatureHeader): void
    {
        if (! $this->verifySignature($rawBody, $signatureHeader)) {
            throw new InvalidSignatureException('WhatsApp webhook signature verification failed.');
        }
    }

    /**
     * Parse a webhook payload into a flat list of events (messages + statuses).
     *
     * @param string|array<string, mixed> $payload
     *
     * @return array<int, WebhookEvent>
     *
     * @throws WhatsAppException On invalid JSON.
     */
    public function parse(string|array $payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (! is_array($decoded)) {
                throw new WhatsAppException('Webhook payload is not valid JSON.');
            }
            $payload = $decoded;
        }

        $events = [];

        foreach (($payload['entry'] ?? []) as $entry) {
            $wabaId = isset($entry['id']) ? (string) $entry['id'] : null;

            foreach (($entry['changes'] ?? []) as $change) {
                if (! is_array($change) || ($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $value = is_array($change['value'] ?? null) ? $change['value'] : [];
                $contacts = is_array($value['contacts'] ?? null) ? $value['contacts'] : [];
                $metadata = is_array($value['metadata'] ?? null) ? $value['metadata'] : [];

                foreach (($value['messages'] ?? []) as $message) {
                    if (is_array($message)) {
                        $events[] = new WebhookEvent(WebhookEvent::TYPE_MESSAGE, $message, $contacts, $metadata, $wabaId);
                    }
                }

                foreach (($value['statuses'] ?? []) as $status) {
                    if (is_array($status)) {
                        $events[] = new WebhookEvent(WebhookEvent::TYPE_STATUS, $status, [], $metadata, $wabaId);
                    }
                }
            }
        }

        return $events;
    }
}
