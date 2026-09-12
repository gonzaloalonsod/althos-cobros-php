<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros;

use AlthoSalud\Cobros\Contract\CobrosClientInterface;
use AlthoSalud\Cobros\Contract\HttpTransportInterface;
use AlthoSalud\Cobros\Dto\ApplicationCredentials;
use AlthoSalud\Cobros\Dto\Checkout;
use AlthoSalud\Cobros\Dto\EntitlementPaid;
use AlthoSalud\Cobros\Exception\ApiException;
use AlthoSalud\Cobros\Exception\ConfigurationException;
use AlthoSalud\Cobros\Exception\InvalidResponseException;
use AlthoSalud\Cobros\Http\CurlTransport;

final readonly class CobrosClient implements CobrosClientInterface
{
    public function __construct(
        private string $baseUrl,
        private ?string $apiKey = null,
        private HttpTransportInterface $transport = new CurlTransport(),
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim($this->baseUrl) && null !== $this->apiKey && '' !== trim($this->apiKey);
    }

    public function withApiKey(string $apiKey): CobrosClientInterface
    {
        return new self($this->baseUrl, $apiKey, $this->transport);
    }

    public function me(): ApplicationCredentials
    {
        return ApplicationCredentials::fromArray($this->request('GET', '/api/v1/me'));
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createCheckout(array $payload, ?string $idempotencyKey = null): Checkout
    {
        $headers = [];
        if (null !== $idempotencyKey && '' !== $idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return Checkout::fromArray($this->request('POST', '/api/v1/checkouts', $payload, $headers));
    }

    public function getCheckout(string $id): Checkout
    {
        $id = trim($id);
        if ('' === $id) {
            throw new \InvalidArgumentException('Checkout id must not be empty.');
        }

        return Checkout::fromArray($this->request('GET', '/api/v1/checkouts/'.rawurlencode($id)));
    }

    public function verifyEntitlementSignature(string $rawBody, string $signatureHeader, string $secret, ?string $idempotencyKey = null): EntitlementPaid
    {
        return EntitlementSignature::parse($rawBody, $signatureHeader, $secret, $idempotencyKey);
    }

    /**
     * @param array<string, mixed>|null $jsonBody
     * @param array<string, string>     $extraHeaders
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $jsonBody = null, array $extraHeaders = []): array
    {
        $baseUrl = rtrim(trim($this->baseUrl), '/');
        if ('' === $baseUrl) {
            throw ConfigurationException::missingBaseUrl();
        }

        $apiKey = null === $this->apiKey ? '' : trim($this->apiKey);
        if ('' === $apiKey) {
            throw ConfigurationException::missingApiKey();
        }

        $headers = array_merge([
            'Authorization' => 'Bearer '.$apiKey,
            'Accept' => 'application/json',
        ], $extraHeaders);

        $body = null;
        if (null !== $jsonBody) {
            $headers['Content-Type'] = 'application/json';
            try {
                $body = json_encode($jsonBody, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw InvalidResponseException::message('request body is not valid JSON.', $exception);
            }
        }

        $response = $this->transport->request($method, $baseUrl.$path, $body, $headers);
        $payload = $this->decode($response->body);

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            $errorCode = \is_string($payload['error'] ?? null) ? $payload['error'] : null;
            $message = \is_string($payload['message'] ?? null)
                ? $payload['message']
                : 'Cobros returned HTTP '.$response->statusCode.'.';

            throw new ApiException($response->statusCode, $errorCode, $message, $payload);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $content): array
    {
        if ('' === $content) {
            throw InvalidResponseException::message('empty response body.');
        }

        try {
            $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw InvalidResponseException::message('body is not valid JSON.', $exception);
        }

        if (!\is_array($payload)) {
            throw InvalidResponseException::message('JSON root must be an object.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }
}
