<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Builders;

/**
 * Helpers for interactive reply buttons and list rows.
 */
final class Button
{
    /**
     * A quick-reply button (max 3 per message).
     *
     * @return array<string, mixed>
     */
    public static function reply(string $id, string $title): array
    {
        return ['type' => 'reply', 'reply' => ['id' => $id, 'title' => $title]];
    }

    /**
     * A list row.
     *
     * @return array<string, mixed>
     */
    public static function row(string $id, string $title, ?string $description = null): array
    {
        $row = ['id' => $id, 'title' => $title];

        if ($description !== null) {
            $row['description'] = $description;
        }

        return $row;
    }

    /**
     * A list section.
     *
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<string, mixed>
     */
    public static function section(string $title, array $rows): array
    {
        return ['title' => $title, 'rows' => $rows];
    }
}
