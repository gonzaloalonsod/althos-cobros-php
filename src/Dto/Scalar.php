<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Dto;

use AlthoSalud\Cobros\Exception\InvalidResponseException;

final class Scalar
{
    public static function int(mixed $value, string $field): int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value) && 1 === preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        }

        throw InvalidResponseException::field($field, 'integer');
    }
}
