<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\Exceptions\InvalidSignatureException;
use TexHub\WhatsApp\Tests\Support\FakeTransport;
use TexHub\WhatsApp\WhatsApp;

final class WebhookTest extends TestCase
{
    private function wa(): WhatsApp
    {
        return new WhatsApp(
            new Config(
                accessToken: 'TOKEN',
                phoneNumberId: '123456',
                appSecret: 'APPSECRET',
                webhookVerifyToken: 'verify-123',
            ),
            new FakeTransport(),
        );
    }

    public function test_challenge_verification(): void
    {
        $handler = $this->wa()->webhooks();

        $this->assertSame('CH123', $handler->verifyChallenge([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'verify-123',
            'hub.challenge' => 'CH123',
        ]));

        $this->assertNull($handler->verifyChallenge([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'WRONG',
            'hub.challenge' => 'CH123',
        ]));
    }

    public function test_signature_verification(): void
    {
        $handler = $this->wa()->webhooks();
        $body = '{"hello":"world"}';
        $sig = 'sha256=' . hash_hmac('sha256', $body, 'APPSECRET');

        $this->assertTrue($handler->verifySignature($body, $sig));
        $this->assertFalse($handler->verifySignature($body, 'sha256=bad'));

        $this->expectException(InvalidSignatureException::class);
        $handler->assertValidSignature($body, 'sha256=bad');
    }

    public function test_parse_incoming_message_and_status(): void
    {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => 'WABA_ID',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => ['phone_number_id' => '123456'],
                        'contacts' => [['wa_id' => '992900123456', 'profile' => ['name' => 'Ali']]],
                        'messages' => [[
                            'from' => '992900123456',
                            'id' => 'wamid.IN',
                            'type' => 'text',
                            'text' => ['body' => 'Привет'],
                        ]],
                    ],
                ]],
            ]],
        ];

        $events = $this->wa()->webhooks()->parse($payload);
        $this->assertCount(1, $events);

        $msg = $events[0];
        $this->assertTrue($msg->isMessage());
        $this->assertSame('992900123456', $msg->from());
        $this->assertSame('text', $msg->messageType());
        $this->assertSame('Привет', $msg->text());

        // status event
        $statusPayload = $payload;
        $statusPayload['entry'][0]['changes'][0]['value'] = [
            'statuses' => [['id' => 'wamid.OUT', 'status' => 'delivered', 'recipient_id' => '992900123456']],
        ];
        $events = $this->wa()->webhooks()->parse($statusPayload);
        $this->assertTrue($events[0]->isStatus());
        $this->assertSame('delivered', $events[0]->status());
    }

    public function test_parse_interactive_reply(): void
    {
        $payload = [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => ['messages' => [[
                        'from' => '992900123456',
                        'id' => 'wamid.IR',
                        'type' => 'interactive',
                        'interactive' => ['type' => 'button_reply', 'button_reply' => ['id' => 'yes', 'title' => 'Да']],
                    ]]],
                ]],
            ]],
        ];

        $event = $this->wa()->webhooks()->parse($payload)[0];
        $reply = $event->interactiveReply();
        $this->assertSame('yes', $reply['id']);
        $this->assertSame('Да', $reply['title']);
    }
}
