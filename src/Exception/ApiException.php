<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Exception;

final class ApiException extends CobrosException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly ?string $apiErrorCode,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message, $statusCode);
    }
}
