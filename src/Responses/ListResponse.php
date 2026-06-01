<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Responses;

/**
 * A paginated list response (`{ "data": [...], "paging": {...} }`).
 */
final class ListResponse extends Response
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function data(): array
    {
        $data = $this->get('data', []);

        return is_array($data) ? $data : [];
    }

    public function nextCursor(): ?string
    {
        $after = $this->get('paging.cursors.after');

        return $after === null ? null : (string) $after;
    }
}
