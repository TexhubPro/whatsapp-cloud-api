<?php

declare(strict_types=1);

namespace TexHub\WhatsApp\Exceptions;

/**
 * Thrown when the SDK is misconfigured (missing token / phone number id, etc.).
 */
class ConfigurationException extends WhatsAppException
{
}
