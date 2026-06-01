<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Resources;

use TexHub\WhatsApp\Config;
use TexHub\WhatsApp\Exceptions\ApiException;
use TexHub\WhatsApp\Exceptions\WhatsAppException;
use TexHub\WhatsApp\Http\RawResponse;
use TexHub\WhatsApp\Http\Transport;
use TexHub\WhatsApp\Responses\ListResponse;
use TexHub\WhatsApp\Responses\Response;

/**
 * Multi-tenant onboarding via WhatsApp Embedded Signup.
 *
 * Flow for letting your SaaS customers connect their own WhatsApp:
 *   1. Front-end runs Embedded Signup → you receive a `code` (and the
 *      customer's WABA id + phone number id).
 *   2. {@see exchangeCode()} — turn the code into the customer's business token.
 *   3. {@see subscribeApp()} — subscribe YOUR app to that WABA's webhooks.
 *   4. {@see registerPhone()} — register the phone number with a 6-digit PIN.
 *   5. Store {token, waba_id, phone_number_id} per tenant and build a
 *      per-tenant client with {@see \TexHub\WhatsApp\WhatsApp::fromArray()}.
 *
 * @see https://developers.facebook.com/docs/whatsapp/embedded-signup
 */
final class OnboardingClient
{
    public function __construct(
        private readonly Config $config,
        private readonly Transport $transport,
    ) {
    }

    /**
     * Exchange an Embedded Signup authorization code for a business access token.
     * Uses the app id/secret (not a user token).
     */
    public function exchangeCode(string $code): Response
    {
        $url = $this->config->url('oauth/access_token') . '?' . http_build_query([
            'client_id' => $this->config->requireAppId(),
            'client_secret' => $this->requireAppSecret(),
            'code' => $code,
        ]);

        return Response::from($this->decode($this->transport->request('GET', $url)));
    }

    /**
     * Subscribe your app to a customer's WABA so its webhooks reach your endpoint.
     * Pass the customer's business token (from {@see exchangeCode()}).
     */
    public function subscribeApp(string $wabaId, string $accessToken): Response
    {
        return Response::from($this->decode($this->transport->request(
            'POST',
            $this->config->url($wabaId . '/subscribed_apps'),
            $this->bearer($accessToken),
        )));
    }

    /**
     * List apps subscribed to a WABA's webhooks.
     */
    public function subscribedApps(string $wabaId, string $accessToken): ListResponse
    {
        return ListResponse::from($this->decode($this->transport->request(
            'GET',
            $this->config->url($wabaId . '/subscribed_apps'),
            $this->bearer($accessToken),
        )));
    }

    /**
     * Register a customer's phone number for Cloud API with a 6-digit PIN.
     */
    public function registerPhone(string $phoneNumberId, string $pin, string $accessToken): Response
    {
        return Response::from($this->decode($this->transport->request(
            'POST',
            $this->config->url($phoneNumberId . '/register'),
            $this->bearer($accessToken),
            json: ['messaging_product' => 'whatsapp', 'pin' => $pin],
        )));
    }

    /**
     * List the phone numbers on a customer's WABA.
     */
    public function phoneNumbers(string $wabaId, string $accessToken): ListResponse
    {
        return ListResponse::from($this->decode($this->transport->request(
            'GET',
            $this->config->url($wabaId . '/phone_numbers'),
            $this->bearer($accessToken),
        )));
    }

    /**
     * Inspect a token (debug_token) — e.g. to read the granted WABA id.
     */
    public function debugToken(string $inputToken): Response
    {
        $appToken = $this->config->requireAppId() . '|' . $this->requireAppSecret();
        $url = $this->config->url('debug_token') . '?' . http_build_query([
            'input_token' => $inputToken,
            'access_token' => $appToken,
        ]);

        return Response::from($this->decode($this->transport->request('GET', $url)));
    }

    /**
     * @return array<string, string>
     */
    private function bearer(string $accessToken): array
    {
        return ['Authorization' => 'Bearer ' . $accessToken, 'Accept' => 'application/json'];
    }

    private function requireAppSecret(): string
    {
        if ($this->config->appSecret === null || trim($this->config->appSecret) === '') {
            throw new WhatsAppException('An app secret (app_secret) is required for onboarding.');
        }

        return $this->config->appSecret;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(RawResponse $response): array
    {
        $decoded = $response->body === '' ? [] : json_decode($response->body, true);

        if (! is_array($decoded)) {
            throw new WhatsAppException('Unexpected non-JSON onboarding response: ' . substr($response->body, 0, 200));
        }

        if (! $response->isSuccessful() || isset($decoded['error'])) {
            throw ApiException::fromResponse($response->statusCode, $decoded);
        }

        return $decoded;
    }
}
