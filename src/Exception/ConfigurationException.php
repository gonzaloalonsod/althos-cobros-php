<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Exception;

final class ConfigurationException extends CobrosException
{
    public static function missingBaseUrl(): self
    {
        return new self('Cobros base URL is not configured.');
    }

    public static function missingApiKey(): self
    {
        return new self('Cobros API key is not configured.');
    }
}
