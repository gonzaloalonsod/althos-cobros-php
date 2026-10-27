<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Http;

use AlthoSalud\Cobros\Contract\HttpTransportInterface;
use AlthoSalud\Cobros\Exception\TransportException;

final class CurlTransport implements HttpTransportInterface
{
    public function __construct(
        private readonly int $timeoutSeconds = 30,
        private readonly int $connectTimeoutSeconds = 10,
        private readonly string $userAgent = 'althosalud-cobros-php/1.1',
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public function request(string $method, string $url, ?string $body = null, array $headers = []): HttpResponse
    {
        $method = mb_strtoupper(trim($method));
        if ('' === $method) {
            throw TransportException::fromPrevious(new \InvalidArgumentException('HTTP method must not be empty.'));
        }

        $handle = curl_init($url);
        if (false === $handle) {
            throw TransportException::fromPrevious(new \RuntimeException('Could not initialize cURL.'));
        }

        if (!isset($headers['User-Agent']) && !isset($headers['user-agent'])) {
            $headers['User-Agent'] = $this->userAgent;
        }

        $curlHeaders = [];
        foreach ($headers as $name => $value) {
            $curlHeaders[] = $name.': '.$value;
        }

        $responseHeaders = [];
        curl_setopt_array($handle, [
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_HEADER => false,
            \CURLOPT_HTTPHEADER => $curlHeaders,
            \CURLOPT_TIMEOUT => $this->timeoutSeconds,
            \CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            \CURLOPT_FOLLOWLOCATION => false,
            \CURLOPT_PROTOCOLS => \CURLPROTO_HTTP | \CURLPROTO_HTTPS,
            \CURLOPT_HEADERFUNCTION => static function ($ch, string $headerLine) use (&$responseHeaders): int {
                $length = \strlen($headerLine);
                $parts = explode(':', $headerLine, 2);
                if (2 === \count($parts)) {
                    $name = strtolower(trim($parts[0]));
                    $value = trim($parts[1]);
                    $responseHeaders[$name][] = $value;
                }

                return $length;
            },
        ]);

        if (null !== $body) {
            curl_setopt($handle, \CURLOPT_POSTFIELDS, $body);
        }

        try {
            $responseBody = curl_exec($handle);
            if (!\is_string($responseBody)) {
                throw TransportException::fromPrevious(new \RuntimeException((string) curl_error($handle)));
            }

            $statusCode = (int) curl_getinfo($handle, \CURLINFO_RESPONSE_CODE);
        } catch (\Throwable $exception) {
            if ($exception instanceof TransportException) {
                throw $exception;
            }

            throw TransportException::fromPrevious($exception);
        } finally {
            curl_close($handle);
        }

        return new HttpResponse($statusCode, $responseBody, $responseHeaders);
    }
}
