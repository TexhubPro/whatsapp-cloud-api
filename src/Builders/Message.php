<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Builders;

/**
 * Helpers for building the message body objects sent to the messages endpoint.
 * Each method returns the type-specific fragment merged into the envelope by
 * {@see \TexHub\WhatsApp\Resources\MessagesClient}.
 */
final class Message
{
    /**
     * @return array<string, mixed>
     */
    public static function text(string $body, bool $previewUrl = false): array
    {
        return ['type' => 'text', 'text' => ['body' => $body, 'preview_url' => $previewUrl]];
    }

    /**
     * Media by public link or by uploaded media id. $type: image|video|audio|document|sticker.
     *
     * @return array<string, mixed>
     */
    public static function media(string $type, string $linkOrId, bool $isId = false, ?string $caption = null, ?string $filename = null): array
    {
        $media = $isId ? ['id' => $linkOrId] : ['link' => $linkOrId];

        if ($caption !== null && in_array($type, ['image', 'video', 'document'], true)) {
            $media['caption'] = $caption;
        }
        if ($filename !== null && $type === 'document') {
            $media['filename'] = $filename;
        }

        return ['type' => $type, $type => $media];
    }

    /**
     * Interactive message with up to 3 reply buttons.
     *
     * @param array<int, array<string, mixed>> $buttons built via {@see Button::reply()}
     *
     * @return array<string, mixed>
     */
    public static function buttons(string $body, array $buttons, ?string $header = null, ?string $footer = null): array
    {
        $interactive = [
            'type' => 'button',
            'body' => ['text' => $body],
            'action' => ['buttons' => $buttons],
        ];

        if ($header !== null) {
            $interactive['header'] = ['type' => 'text', 'text' => $header];
        }
        if ($footer !== null) {
            $interactive['footer'] = ['text' => $footer];
        }

        return ['type' => 'interactive', 'interactive' => $interactive];
    }

    /**
     * Interactive list message.
     *
     * @param array<int, array<string, mixed>> $sections built via {@see Button::section()}
     *
     * @return array<string, mixed>
     */
    public static function list(string $body, string $buttonText, array $sections, ?string $header = null, ?string $footer = null): array
    {
        $interactive = [
            'type' => 'list',
            'body' => ['text' => $body],
            'action' => ['button' => $buttonText, 'sections' => $sections],
        ];

        if ($header !== null) {
            $interactive['header'] = ['type' => 'text', 'text' => $header];
        }
        if ($footer !== null) {
            $interactive['footer'] = ['text' => $footer];
        }

        return ['type' => 'interactive', 'interactive' => $interactive];
    }

    /**
     * A template message.
     *
     * @param array<int, array<string, mixed>> $components
     *
     * @return array<string, mixed>
     */
    public static function template(string $name, string $languageCode = 'en_US', array $components = []): array
    {
        $template = ['name' => $name, 'language' => ['code' => $languageCode]];

        if ($components !== []) {
            $template['components'] = $components;
        }

        return ['type' => 'template', 'template' => $template];
    }

    /**
     * @return array<string, mixed>
     */
    public static function location(float $latitude, float $longitude, ?string $name = null, ?string $address = null): array
    {
        $location = ['latitude' => $latitude, 'longitude' => $longitude];

        if ($name !== null) {
            $location['name'] = $name;
        }
        if ($address !== null) {
            $location['address'] = $address;
        }

        return ['type' => 'location', 'location' => $location];
    }

    /**
     * React to a message with an emoji (empty string removes the reaction).
     *
     * @return array<string, mixed>
     */
    public static function reaction(string $messageId, string $emoji): array
    {
        return ['type' => 'reaction', 'reaction' => ['message_id' => $messageId, 'emoji' => $emoji]];
    }
}
