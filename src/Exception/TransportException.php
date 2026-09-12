<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Exception;

final class TransportException extends CobrosException
{
    public static function fromPrevious(\Throwable $previous): self
    {
        return new self('Could not contact Cobros.', 0, $previous);
    }
}
