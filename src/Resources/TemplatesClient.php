<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Resources;

use TexHub\WhatsApp\Responses\ListResponse;
use TexHub\WhatsApp\Responses\Response;

/**
 * Message templates management (requires the business account id).
 *
 * @see https://developers.facebook.com/docs/whatsapp/business-management-api/message-templates
 */
final class TemplatesClient extends Resource
{
    /**
     * List message templates.
     *
     * @param array<string, mixed> $query e.g. ['limit' => 50]
     */
    public function list(array $query = []): ListResponse
    {
        return ListResponse::from($this->http->get(
            $this->config->requireBusinessAccountId() . '/message_templates',
            $query,
        ));
    }

    /**
     * Create a message template.
     *
     * @param array<int, array<string, mixed>> $components
     */
    public function create(string $name, string $category, string $language, array $components): Response
    {
        return Response::from($this->http->postJson($this->config->requireBusinessAccountId() . '/message_templates', [
            'name' => $name,
            'category' => $category,
            'language' => $language,
            'components' => $components,
        ]));
    }

    /**
     * Delete a template by name.
     */
    public function delete(string $name): Response
    {
        return Response::from($this->http->delete(
            $this->config->requireBusinessAccountId() . '/message_templates?name=' . rawurlencode($name),
        ));
    }
}
