<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Http;

use TexHub\WhatsApp\Exceptions\ConfigurationException;

/**
 * A file to upload in a multipart request.
 */
final class FileParam
{
    private function __construct(
        public readonly string $path,
        public readonly string $filename,
        public readonly ?string $contentType,
    ) {
    }

    public static function fromPath(string $path, ?string $filename = null, ?string $contentType = null): self
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new ConfigurationException(sprintf('File "%s" does not exist or is not readable.', $path));
        }

        return new self($path, $filename ?? basename($path), $contentType);
    }
}
