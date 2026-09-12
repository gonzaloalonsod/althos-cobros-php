<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Dto;

use AlthoSalud\Cobros\Exception\InvalidResponseException;

final readonly class EntitlementPaid
{
    public function __construct(
        public string $event,
        public string $checkoutId,
        public mixed $lineId,
        public string $sku,
        public int $amountCents,
        public string $currency,
        public ?string $periodStart,
        public ?string $periodEnd,
        public ?string $customerExternalId,
        public ?string $paymentId,
        public ?string $entitlementRef,
        public ?string $idempotencyKey,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload, ?string $idempotencyKey = null): self
    {
        $event = $payload['event'] ?? null;
        if ('entitlement.paid' !== $event) {
            throw InvalidResponseException::field('event', 'entitlement.paid');
        }

        $sku = $payload['sku'] ?? null;
        if (!\is_string($sku) || '' === $sku) {
            throw InvalidResponseException::field('sku', 'non-empty string');
        }

        $amount = Scalar::int($payload['amount_cents'] ?? null, 'amount_cents');

        $currency = $payload['currency'] ?? null;
        if (!\is_string($currency) || '' === $currency) {
            throw InvalidResponseException::field('currency', 'non-empty string');
        }

        $checkoutId = $payload['checkout_id'] ?? '';
        if (!\is_string($checkoutId)) {
            $checkoutId = '';
        }

        $periodStart = $payload['period_start'] ?? null;
        $periodEnd = $payload['period_end'] ?? null;
        $customer = $payload['customer_external_id'] ?? null;
        $paymentId = $payload['payment_id'] ?? null;
        $ref = $payload['entitlement_ref'] ?? null;

        return new self(
            event: $event,
            checkoutId: $checkoutId,
            lineId: $payload['line_id'] ?? null,
            sku: $sku,
            amountCents: $amount,
            currency: $currency,
            periodStart: \is_string($periodStart) ? $periodStart : null,
            periodEnd: \is_string($periodEnd) ? $periodEnd : null,
            customerExternalId: \is_string($customer) ? $customer : null,
            paymentId: \is_string($paymentId) ? $paymentId : null,
            entitlementRef: \is_string($ref) ? $ref : null,
            idempotencyKey: $idempotencyKey,
        );
    }
}
