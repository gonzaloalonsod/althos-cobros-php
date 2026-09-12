<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Http;

final readonly class HttpResponse
{
    public function __construct(
        public int $statusCode,
        public string $body,
    ) {
    }
}
