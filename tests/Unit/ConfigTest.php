<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\Exceptions\ConfigurationException;

final class ConfigTest extends TestCase
{
    public function test_requires_token_and_phone_number_id(): void
    {
        $this->expectException(ConfigurationException::class);
        new Config(accessToken: '', phoneNumberId: '123');
    }

    public function test_url_prefixes_version(): void
    {
        $config = new Config(accessToken: 't', phoneNumberId: '123');
        $this->assertSame('https://graph.facebook.com/v23.0/123/messages', $config->url('123/messages'));
    }

    public function test_require_business_account_id(): void
    {
        $this->expectException(ConfigurationException::class);
        (new Config(accessToken: 't', phoneNumberId: '123'))->requireBusinessAccountId();
    }

    public function test_from_array(): void
    {
        $config = Config::fromArray([
            'access_token' => 't',
            'phone_number_id' => '123',
            'business_account_id' => 'waba',
            'app_secret' => 'sec',
        ]);

        $this->assertSame('waba', $config->requireBusinessAccountId());
        $this->assertSame('sec', $config->appSecret);
    }
}
