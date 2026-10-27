<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Http;

final readonly class HttpResponse
{
    /**
     * @param array<string, list<string>> $headers lowercase header name => values
     */
    public function __construct(
        public int $statusCode,
        public string $body,
        public array $headers = [],
    ) {
    }

    public function header(string $name): ?string
    {
        $values = $this->headers[strtolower($name)] ?? null;
        if (null === $values || [] === $values) {
            return null;
        }

        return $values[0];
    }
}
