<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Http;

use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\Exceptions\ApiException;
use TexHub\WhatsApp\Exceptions\WhatsAppException;

/**
 * Graph API HTTP wrapper: builds versioned URLs, applies the Bearer token,
 * decodes JSON and converts error payloads into {@see ApiException}.
 */
final class HttpClient
{
    public function __construct(
        private readonly Config $config,
        private readonly Transport $transport,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $url = $this->config->url($path);
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $this->decode($this->transport->request('GET', $url, $this->headers()));
    }

    /**
     * @param array<string, mixed> $json
     *
     * @return array<string, mixed>
     */
    public function postJson(string $path, array $json): array
    {
        return $this->decode($this->transport->request('POST', $this->config->url($path), $this->headers(), json: $json));
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    public function postMultipart(string $path, array $fields): array
    {
        return $this->decode($this->transport->request('POST', $this->config->url($path), $this->headers(), multipart: $fields));
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $path): array
    {
        return $this->decode($this->transport->request('DELETE', $this->config->url($path), $this->headers()));
    }

    /**
     * GET returning the raw response (for binary media downloads). Throws on HTTP error.
     */
    public function getRaw(string $url): RawResponse
    {
        $response = $this->transport->request('GET', $url, $this->headers());

        if (! $response->isSuccessful()) {
            $this->decode($response);
        }

        return $response;
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->config->accessToken,
            'Accept' => 'application/json',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(RawResponse $response): array
    {
        $decoded = $response->body === '' ? [] : json_decode($response->body, true);

        if (! is_array($decoded)) {
            if ($response->isSuccessful()) {
                throw new WhatsAppException('Unexpected non-JSON response from WhatsApp: ' . substr($response->body, 0, 200));
            }

            throw new ApiException('WhatsApp Cloud API error (HTTP ' . $response->statusCode . ')', $response->statusCode);
        }

        if (! $response->isSuccessful() || isset($decoded['error'])) {
            throw ApiException::fromResponse($response->statusCode, $decoded);
        }

        return $decoded;
    }
}
