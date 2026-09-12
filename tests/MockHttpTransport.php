<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Tests;

use AlthoSalud\Cobros\Contract\HttpTransportInterface;
use AlthoSalud\Cobros\Http\HttpResponse;

final class MockHttpTransport implements HttpTransportInterface
{
    /** @var list<array{method: string, url: string, body: string|null, headers: array<string, string>}> */
    public array $requests = [];

    /**
     * @param list<HttpResponse> $responses
     */
    public function __construct(
        private array $responses = [],
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public function request(string $method, string $url, ?string $body = null, array $headers = []): HttpResponse
    {
        $this->requests[] = [
            'method' => $method,
            'url' => $url,
            'body' => $body,
            'headers' => $headers,
        ];

        if ([] === $this->responses) {
            throw new \RuntimeException('No mock HTTP response configured.');
        }

        return array_shift($this->responses);
    }
}
