<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Resources;

use TexHub\WhatsApp\Responses\ListResponse;
use TexHub\WhatsApp\Responses\Response;

/**
 * Business profile & phone numbers.
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/reference/business-profiles
 */
final class ProfileClient extends Resource
{
    public const PROFILE_FIELDS = [
        'about', 'address', 'description', 'email', 'profile_picture_url',
        'websites', 'vertical', 'messaging_product',
    ];

    /**
     * Get the business profile for the configured phone number.
     *
     * @param array<int, string> $fields
     */
    public function get(array $fields = self::PROFILE_FIELDS): Response
    {
        return Response::from($this->http->get(
            $this->config->phoneNumberId . '/whatsapp_business_profile',
            ['fields' => implode(',', $fields)],
        ));
    }

    /**
     * Update the business profile.
     *
     * @param array<string, mixed> $fields e.g. ['about' => '...', 'email' => '...']
     */
    public function update(array $fields): Response
    {
        return Response::from($this->http->postJson(
            $this->config->phoneNumberId . '/whatsapp_business_profile',
            ['messaging_product' => 'whatsapp'] + $fields,
        ));
    }

    /**
     * Get details of the configured phone number.
     */
    public function phoneNumber(): Response
    {
        return Response::from($this->http->get($this->config->phoneNumberId));
    }

    /**
     * List all phone numbers on the WhatsApp Business Account.
     */
    public function phoneNumbers(): ListResponse
    {
        return ListResponse::from($this->http->get($this->config->requireBusinessAccountId() . '/phone_numbers'));
    }
}
