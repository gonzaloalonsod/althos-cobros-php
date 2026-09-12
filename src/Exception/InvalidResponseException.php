<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Exception;

final class InvalidResponseException extends CobrosException
{
    public static function message(string $message, ?\Throwable $previous = null): self
    {
        return new self('Invalid Cobros response: '.$message, 0, $previous);
    }

    public static function field(string $field, string $expected, ?\Throwable $previous = null): self
    {
        return self::message(\sprintf('field "%s" must be %s.', $field, $expected), $previous);
    }
}
