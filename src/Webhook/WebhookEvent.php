<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Webhook;

/**
 * A normalized webhook event: either an incoming message or a status update.
 */
final class WebhookEvent
{
    public const TYPE_MESSAGE = 'message';
    public const TYPE_STATUS = 'status';

    /**
     * @param array<string, mixed> $data     The message or status payload.
     * @param array<string, mixed> $contacts Sender contact profiles (for messages).
     * @param array<string, mixed> $metadata Phone number metadata.
     */
    public function __construct(
        public readonly string $type,
        public readonly array $data,
        public readonly array $contacts = [],
        public readonly array $metadata = [],
    ) {
    }

    public function isMessage(): bool
    {
        return $this->type === self::TYPE_MESSAGE;
    }

    public function isStatus(): bool
    {
        return $this->type === self::TYPE_STATUS;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /** Sender phone number (wa_id) for incoming messages, or recipient id for statuses. */
    public function from(): ?string
    {
        return isset($this->data['from']) ? (string) $this->data['from']
            : (isset($this->data['recipient_id']) ? (string) $this->data['recipient_id'] : null);
    }

    public function messageId(): ?string
    {
        return isset($this->data['id']) ? (string) $this->data['id'] : null;
    }

    /** Message type: text, image, interactive, button, audio, document, location, … */
    public function messageType(): ?string
    {
        return isset($this->data['type']) ? (string) $this->data['type'] : null;
    }

    /** Text body for text messages. */
    public function text(): ?string
    {
        return $this->data['text']['body'] ?? null;
    }

    /**
     * For interactive replies: the id/title the user tapped (button or list).
     *
     * @return array{id: ?string, title: ?string}
     */
    public function interactiveReply(): array
    {
        $interactive = $this->data['interactive'] ?? [];
        $reply = $interactive['button_reply'] ?? $interactive['list_reply'] ?? [];

        return [
            'id' => isset($reply['id']) ? (string) $reply['id'] : null,
            'title' => isset($reply['title']) ? (string) $reply['title'] : null,
        ];
    }

    /** Status value for status events: sent, delivered, read, failed. */
    public function status(): ?string
    {
        return isset($this->data['status']) ? (string) $this->data['status'] : null;
    }
}
