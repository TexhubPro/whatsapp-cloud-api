<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\WhatsApp\Builders\Button;
use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\Exceptions\ApiException;
use TexHub\WhatsApp\Tests\Support\FakeTransport;
use TexHub\WhatsApp\WhatsApp;

final class MessagesTest extends TestCase
{
    private function wa(FakeTransport $t): WhatsApp
    {
        return new WhatsApp(new Config(accessToken: 'TOKEN', phoneNumberId: '123456'), $t);
    }

    public function test_send_text_posts_json_with_bearer(): void
    {
        $t = new FakeTransport();
        $response = $this->wa($t)->sendText('992900123456', 'Привет!');

        $this->assertSame('wamid.TEST', $response->messageId());
        $this->assertStringContainsString('https://graph.facebook.com/v23.0/123456/messages', $t->lastUrl());
        $this->assertSame('Bearer TOKEN', $t->last()['headers']['Authorization']);

        $json = $t->last()['json'];
        $this->assertSame('whatsapp', $json['messaging_product']);
        $this->assertSame('992900123456', $json['to']);
        $this->assertSame('text', $json['type']);
        $this->assertSame('Привет!', $json['text']['body']);
    }

    public function test_send_buttons_builds_interactive(): void
    {
        $t = new FakeTransport();

        $this->wa($t)->messages()->buttons('992900123456', 'Выберите:', [
            Button::reply('yes', 'Да'),
            Button::reply('no', 'Нет'),
        ], header: 'Заголовок', footer: 'Низ');

        $interactive = $t->last()['json']['interactive'];
        $this->assertSame('button', $interactive['type']);
        $this->assertSame('Выберите:', $interactive['body']['text']);
        $this->assertSame('yes', $interactive['action']['buttons'][0]['reply']['id']);
        $this->assertSame('Заголовок', $interactive['header']['text']);
    }

    public function test_send_list(): void
    {
        $t = new FakeTransport();

        $this->wa($t)->messages()->list('992900123456', 'Меню', 'Открыть', [
            Button::section('Раздел', [Button::row('a', 'Пункт A', 'описание')]),
        ]);

        $action = $t->last()['json']['interactive']['action'];
        $this->assertSame('Открыть', $action['button']);
        $this->assertSame('Пункт A', $action['sections'][0]['rows'][0]['title']);
    }

    public function test_template_message(): void
    {
        $t = new FakeTransport();

        $this->wa($t)->messages()->template('992900123456', 'hello_world', 'en_US');

        $tpl = $t->last()['json']['template'];
        $this->assertSame('hello_world', $tpl['name']);
        $this->assertSame('en_US', $tpl['language']['code']);
    }

    public function test_mark_read(): void
    {
        $t = (new FakeTransport())->push(['success' => true]);

        $this->wa($t)->messages()->markRead('wamid.ABC');

        $json = $t->last()['json'];
        $this->assertSame('read', $json['status']);
        $this->assertSame('wamid.ABC', $json['message_id']);
    }

    public function test_api_error_is_parsed(): void
    {
        $t = (new FakeTransport())->push([
            'error' => ['message' => 'Invalid OAuth access token', 'type' => 'OAuthException', 'code' => 190],
        ], 401);

        try {
            $this->wa($t)->sendText('992900123456', 'hi');
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame(190, $e->errorCode);
            $this->assertTrue($e->isTokenError());
        }
    }
}
