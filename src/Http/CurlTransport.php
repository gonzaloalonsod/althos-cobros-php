<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Http;

use AlthoSalud\Cobros\Contract\HttpTransportInterface;
use AlthoSalud\Cobros\Exception\TransportException;

final class CurlTransport implements HttpTransportInterface
{
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

        $curlHeaders = [];
        foreach ($headers as $name => $value) {
            $curlHeaders[] = $name.': '.$value;
        }

        curl_setopt_array($handle, [
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_HEADER => false,
            \CURLOPT_HTTPHEADER => $curlHeaders,
            \CURLOPT_TIMEOUT => 30,
            \CURLOPT_CONNECTTIMEOUT => 10,
            \CURLOPT_FOLLOWLOCATION => false,
            \CURLOPT_PROTOCOLS => \CURLPROTO_HTTP | \CURLPROTO_HTTPS,
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

        return new HttpResponse($statusCode, $responseBody);
    }
}
