<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Resources;

use TexHub\WhatsApp\Builders\Message;
use TexHub\WhatsApp\Responses\Response;

/**
 * Send messages of every type through the WhatsApp Cloud API.
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages
 */
final class MessagesClient extends Resource
{
    /**
     * Send a pre-built message body (see {@see Message}).
     *
     * @param array<string, mixed> $message A fragment like Message::text(...).
     */
    public function send(string $to, array $message): Response
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
        ] + $message;

        return Response::from($this->http->postJson($this->endpoint(), $payload));
    }

    public function text(string $to, string $body, bool $previewUrl = false): Response
    {
        return $this->send($to, Message::text($body, $previewUrl));
    }

    public function image(string $to, string $link, ?string $caption = null): Response
    {
        return $this->send($to, Message::media('image', $link, caption: $caption));
    }

    public function document(string $to, string $link, ?string $caption = null, ?string $filename = null): Response
    {
        return $this->send($to, Message::media('document', $link, caption: $caption, filename: $filename));
    }

    public function video(string $to, string $link, ?string $caption = null): Response
    {
        return $this->send($to, Message::media('video', $link, caption: $caption));
    }

    public function audio(string $to, string $link): Response
    {
        return $this->send($to, Message::media('audio', $link));
    }

    /**
     * Send an uploaded media by its media id.
     */
    public function mediaById(string $to, string $type, string $mediaId, ?string $caption = null): Response
    {
        return $this->send($to, Message::media($type, $mediaId, isId: true, caption: $caption));
    }

    /**
     * Interactive reply buttons (max 3).
     *
     * @param array<int, array<string, mixed>> $buttons
     */
    public function buttons(string $to, string $body, array $buttons, ?string $header = null, ?string $footer = null): Response
    {
        return $this->send($to, Message::buttons($body, $buttons, $header, $footer));
    }

    /**
     * Interactive list message.
     *
     * @param array<int, array<string, mixed>> $sections
     */
    public function list(string $to, string $body, string $buttonText, array $sections, ?string $header = null, ?string $footer = null): Response
    {
        return $this->send($to, Message::list($body, $buttonText, $sections, $header, $footer));
    }

    /**
     * Send an approved template (the only way to message users outside the 24h window).
     *
     * @param array<int, array<string, mixed>> $components
     */
    public function template(string $to, string $name, string $languageCode = 'en_US', array $components = []): Response
    {
        return $this->send($to, Message::template($name, $languageCode, $components));
    }

    public function location(string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null): Response
    {
        return $this->send($to, Message::location($latitude, $longitude, $name, $address));
    }

    public function reaction(string $to, string $messageId, string $emoji): Response
    {
        return $this->send($to, Message::reaction($messageId, $emoji));
    }

    /**
     * Mark an inbound message as read.
     */
    public function markRead(string $messageId): Response
    {
        return Response::from($this->http->postJson($this->endpoint(), [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ]));
    }

    /**
     * Mark as read and show the typing indicator.
     */
    public function markReadTyping(string $messageId): Response
    {
        return Response::from($this->http->postJson($this->endpoint(), [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
            'typing_indicator' => ['type' => 'text'],
        ]));
    }

    private function endpoint(): string
    {
        return $this->config->phoneNumberId . '/messages';
    }
}
