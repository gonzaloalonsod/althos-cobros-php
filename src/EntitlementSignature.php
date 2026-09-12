<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros;

use AlthoSalud\Cobros\Dto\EntitlementPaid;
use AlthoSalud\Cobros\Exception\InvalidResponseException;
use AlthoSalud\Cobros\Exception\InvalidSignatureException;

final class EntitlementSignature
{
    public static function isValid(string $rawBody, string $signatureHeader, string $secret): bool
    {
        if ('' === $secret || '' === $signatureHeader) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);
        $provided = trim($signatureHeader);
        if (str_starts_with(strtolower($provided), 'sha256=')) {
            $provided = substr($provided, 7);
        }

        return hash_equals($expected, $provided);
    }

    public static function parse(string $rawBody, string $signatureHeader, string $secret, ?string $idempotencyKey = null): EntitlementPaid
    {
        if (!self::isValid($rawBody, $signatureHeader, $secret)) {
            throw InvalidSignatureException::mismatch();
        }

        try {
            $payload = json_decode($rawBody, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw InvalidResponseException::message('body is not valid JSON.', $exception);
        }

        if (!\is_array($payload)) {
            throw InvalidResponseException::message('JSON root must be an object.');
        }

        /** @var array<string, mixed> $payload */
        return EntitlementPaid::fromArray($payload, $idempotencyKey);
    }
}
