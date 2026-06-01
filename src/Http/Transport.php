<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Http;

use TexHub\WhatsApp\Exceptions\TransportException;

/**
 * HTTP transport abstraction so the SDK has no hard dependency on a specific
 * HTTP client and can be fully unit-tested with a fake.
 */
interface Transport
{
    /**
     * @param array<string, string>     $headers
     * @param array<string, mixed>|null $json      JSON body.
     * @param array<string, mixed>|null $multipart Multipart fields (values may be {@see FileParam}).
     *
     * @throws TransportException On connection/network failures.
     */
    public function request(
        string $method,
        string $url,
        array $headers = [],
        ?array $json = null,
        ?array $multipart = null,
    ): RawResponse;
}
