<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Exception;

final class InvalidSignatureException extends CobrosException
{
    public static function mismatch(): self
    {
        return new self('X-Cobros-Signature does not match the webhook secret.');
    }
}
