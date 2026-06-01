<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Exceptions;

/**
 * Thrown on a network/transport-level failure before a valid response could be parsed.
 */
class TransportException extends WhatsAppException
{
}
