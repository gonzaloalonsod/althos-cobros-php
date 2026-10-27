<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Exception;

final class ApiException extends CobrosException
{
    /**
     * @param array<string, mixed>        $details
     * @param array<string, list<string>> $responseHeaders
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly ?string $apiErrorCode,
        string $message,
        public readonly array $details = [],
        public readonly array $responseHeaders = [],
    ) {
        parent::__construct($message, $statusCode);
    }

    public function responseHeader(string $name): ?string
    {
        $values = $this->responseHeaders[strtolower($name)] ?? null;
        if (null === $values || [] === $values) {
            return null;
        }

        return $values[0];
    }
}
