<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\Tests\Support\FakeTransport;
use TexHub\WhatsApp\WhatsApp;

final class MultiTenantTest extends TestCase
{
    private function wa(FakeTransport $t): WhatsApp
    {
        return new WhatsApp(new Config(
            accessToken: 'TOKEN',
            phoneNumberId: '123456',
            appSecret: 'APPSECRET',
            appId: 'APP123',
        ), $t);
    }

    public function test_webhook_event_exposes_tenant_routing_keys(): void
    {
        $payload = [
            'entry' => [[
                'id' => 'WABA_TENANT_1',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => ['phone_number_id' => 'PN_TENANT_1', 'display_phone_number' => '992900000000'],
                        'contacts' => [['wa_id' => '992900123456', 'profile' => ['name' => 'Ali']]],
                        'messages' => [['from' => '992900123456', 'id' => 'wamid.1', 'type' => 'text', 'text' => ['body' => 'hi']]],
                    ],
                ]],
            ]],
        ];

        $event = $this->wa(new FakeTransport())->webhooks()->parse($payload)[0];

        // These are what a SaaS backend uses to route to the right tenant.
        $this->assertSame('PN_TENANT_1', $event->phoneNumberId());
        $this->assertSame('WABA_TENANT_1', $event->wabaId());
        $this->assertSame('992900000000', $event->displayPhoneNumber());
        $this->assertSame('Ali', $event->contactName());
    }

    public function test_per_tenant_clients_are_isolated(): void
    {
        $t1 = new FakeTransport();
        $t2 = new FakeTransport();

        $tenantA = WhatsApp::fromArray(['access_token' => 'TOKEN_A', 'phone_number_id' => 'PN_A'], $t1);
        $tenantB = WhatsApp::fromArray(['access_token' => 'TOKEN_B', 'phone_number_id' => 'PN_B'], $t2);

        $tenantA->sendText('992900000001', 'A');
        $tenantB->sendText('992900000002', 'B');

        $this->assertSame('Bearer TOKEN_A', $t1->last()['headers']['Authorization']);
        $this->assertStringContainsString('/PN_A/messages', $t1->lastUrl());
        $this->assertSame('Bearer TOKEN_B', $t2->last()['headers']['Authorization']);
        $this->assertStringContainsString('/PN_B/messages', $t2->lastUrl());
    }

    public function test_onboarding_exchange_code(): void
    {
        $t = (new FakeTransport())->push(['access_token' => 'BIZ_TOKEN', 'token_type' => 'bearer']);

        $token = $this->wa($t)->onboarding()->exchangeCode('CODE123');

        $this->assertSame('BIZ_TOKEN', $token->get('access_token'));
        $url = $t->lastUrl();
        $this->assertStringContainsString('/oauth/access_token', $url);
        $this->assertStringContainsString('client_id=APP123', $url);
        $this->assertStringContainsString('code=CODE123', $url);
    }

    public function test_onboarding_subscribe_and_register(): void
    {
        $t = new FakeTransport();
        $t->push(['success' => true])->push(['success' => true]);

        $onboarding = $this->wa($t)->onboarding();
        $onboarding->subscribeApp('WABA_X', 'BIZ_TOKEN');
        $this->assertStringContainsString('/WABA_X/subscribed_apps', $t->history[0]['url']);
        $this->assertSame('Bearer BIZ_TOKEN', $t->history[0]['headers']['Authorization']);

        $onboarding->registerPhone('PN_X', '123456', 'BIZ_TOKEN');
        $this->assertStringContainsString('/PN_X/register', $t->history[1]['url']);
        $this->assertSame('123456', $t->history[1]['json']['pin']);
    }
}
