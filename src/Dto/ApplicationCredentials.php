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
        public ?string $name = null,
        public ?int $rateLimitPerMinute = null,
        public int $commissionBps = 0,
        public string $commissionMode = 'none',
        public ?string $keyPrefix = null,
        public ?string $keyLabel = null,
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
        $keyPrefix = null;
        $keyLabel = null;
        $apiKey = $payload['api_key'] ?? null;
        if (\is_array($apiKey)) {
            if (isset($apiKey['scopes']) && \is_array($apiKey['scopes'])) {
                foreach ($apiKey['scopes'] as $scope) {
                    if (\is_string($scope)) {
                        $scopes[] = $scope;
                    }
                }
            }
            $keyPrefix = \is_string($apiKey['prefix'] ?? null) ? $apiKey['prefix'] : null;
            $keyLabel = \is_string($apiKey['label'] ?? null) ? $apiKey['label'] : null;
        }

        $name = $application['name'] ?? null;
        $rateLimit = $application['rate_limit_per_minute'] ?? null;
        $commissionBps = isset($application['commission_bps'])
            ? Scalar::int($application['commission_bps'], 'commission_bps')
            : 0;
        $commissionMode = $application['commission_mode'] ?? 'none';
        if (!\is_string($commissionMode) || '' === $commissionMode) {
            $commissionMode = 'none';
        }

        return new self(
            id: self::integer($application, 'id'),
            slug: self::string($application, 'slug'),
            status: self::string($application, 'status'),
            scopes: $scopes,
            name: \is_string($name) ? $name : null,
            rateLimitPerMinute: \is_int($rateLimit) ? $rateLimit : null,
            commissionBps: $commissionBps,
            commissionMode: $commissionMode,
            keyPrefix: $keyPrefix,
            keyLabel: $keyLabel,
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
