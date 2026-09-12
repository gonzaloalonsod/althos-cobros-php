<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Dto;

use AlthoSalud\Cobros\Exception\InvalidResponseException;

final readonly class ApplicationCredentials
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public int $id,
        public string $slug,
        public string $status,
        public array $scopes = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $application = $payload;
        if (isset($payload['application']) && \is_array($payload['application'])) {
            $application = $payload['application'];
        }

        $scopes = [];
        $apiKey = $payload['api_key'] ?? null;
        if (\is_array($apiKey) && isset($apiKey['scopes']) && \is_array($apiKey['scopes'])) {
            foreach ($apiKey['scopes'] as $scope) {
                if (\is_string($scope)) {
                    $scopes[] = $scope;
                }
            }
        }

        return new self(
            id: self::integer($application, 'id'),
            slug: self::string($application, 'slug'),
            status: self::string($application, 'status'),
            scopes: $scopes,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function string(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;
        if (!\is_string($value) || '' === $value) {
            throw InvalidResponseException::field($field, 'non-empty string');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function integer(array $payload, string $field): int
    {
        $value = $payload[$field] ?? null;
        if (!\is_int($value)) {
            throw InvalidResponseException::field($field, 'integer');
        }

        return $value;
    }
}
