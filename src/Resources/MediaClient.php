<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Resources;

use TexHub\WhatsApp\Http\FileParam;
use TexHub\WhatsApp\Responses\Response;

/**
 * Media — upload, look up and download media for messages.
 *
 * @see https://developers.facebook.com/docs/whatsapp/cloud-api/reference/media
 */
final class MediaClient extends Resource
{
    /**
     * Upload a media file; returns a media id usable in {@see MessagesClient::mediaById()}.
     */
    public function upload(string $path, ?string $mimeType = null): Response
    {
        $file = FileParam::fromPath($path, contentType: $mimeType);

        return Response::from($this->http->postMultipart($this->config->phoneNumberId . '/media', [
            'messaging_product' => 'whatsapp',
            'type' => $mimeType ?? '',
            'file' => $file,
        ]));
    }

    /**
     * Retrieve a media object (its temporary download URL, mime type, size, …).
     */
    public function get(string $mediaId): Response
    {
        return Response::from($this->http->get($mediaId));
    }

    /**
     * Resolve a media id to its (short-lived) download URL.
     */
    public function url(string $mediaId): ?string
    {
        $url = $this->get($mediaId)->get('url');

        return $url === null ? null : (string) $url;
    }

    /**
     * Download the binary contents of a media id.
     */
    public function download(string $mediaId): string
    {
        $url = (string) $this->url($mediaId);

        return $this->http->getRaw($url)->body;
    }

    /**
     * Delete an uploaded media object.
     */
    public function delete(string $mediaId): Response
    {
        return Response::from($this->http->delete($mediaId));
    }
}
