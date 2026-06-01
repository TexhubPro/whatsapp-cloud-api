<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Http;

/**
 * Raw transport result: HTTP status code, undecoded body and response headers.
 */
final class RawResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
        public readonly array $headers = [],
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
