<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Dto;

use AlthoSalud\Cobros\Exception\InvalidResponseException;

final readonly class CheckoutLine
{
    public function __construct(
        public mixed $id,
        public int $amountCents,
        public string $currency,
        public string $description,
        public string $sku,
        public ?string $periodStart = null,
        public ?string $periodEnd = null,
        public ?string $entitlementRef = null,
        public ?string $customerExternalId = null,
        public ?string $targetApplicationSlug = null,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $currency = $payload['currency'] ?? null;
        if (!\is_string($currency) || '' === $currency) {
            throw InvalidResponseException::field('currency', 'non-empty string');
        }

        $sku = $payload['sku'] ?? null;
        if (!\is_string($sku) || '' === $sku) {
            throw InvalidResponseException::field('sku', 'non-empty string');
        }

        $description = $payload['description'] ?? '';
        if (!\is_string($description)) {
            throw InvalidResponseException::field('description', 'string');
        }

        $periodStart = $payload['period_start'] ?? null;
        $periodEnd = $payload['period_end'] ?? null;
        $ref = $payload['entitlement_ref'] ?? null;
        $customer = $payload['customer_external_id'] ?? null;
        $target = $payload['target_application_slug'] ?? null;

        return new self(
            id: $payload['id'] ?? null,
            amountCents: Scalar::int($payload['amount_cents'] ?? null, 'amount_cents'),
            currency: $currency,
            description: $description,
            sku: $sku,
            periodStart: \is_string($periodStart) ? $periodStart : null,
            periodEnd: \is_string($periodEnd) ? $periodEnd : null,
            entitlementRef: \is_string($ref) ? $ref : null,
            customerExternalId: \is_string($customer) ? $customer : null,
            targetApplicationSlug: \is_string($target) ? $target : null,
        );
    }
}
