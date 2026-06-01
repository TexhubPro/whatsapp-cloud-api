<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Resources;

use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\Http\HttpClient;

abstract class Resource
{
    public function __construct(
        protected readonly HttpClient $http,
        protected readonly Config $config,
    ) {
    }
}
