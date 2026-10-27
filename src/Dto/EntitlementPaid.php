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
        public ?int $checkoutSubtotalAmountCents = null,
        public ?int $checkoutCommissionBps = null,
        public ?string $checkoutCommissionMode = null,
        public ?int $checkoutCommissionAmountCents = null,
        public ?int $checkoutTotalAmountCents = null,
        public ?int $checkoutNetAmountCents = null,
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
        $mode = $payload['checkout_commission_mode'] ?? null;

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
            checkoutSubtotalAmountCents: isset($payload['checkout_subtotal_amount_cents'])
                ? Scalar::int($payload['checkout_subtotal_amount_cents'], 'checkout_subtotal_amount_cents')
                : null,
            checkoutCommissionBps: isset($payload['checkout_commission_bps'])
                ? Scalar::int($payload['checkout_commission_bps'], 'checkout_commission_bps')
                : null,
            checkoutCommissionMode: \is_string($mode) ? $mode : null,
            checkoutCommissionAmountCents: isset($payload['checkout_commission_amount_cents'])
                ? Scalar::int($payload['checkout_commission_amount_cents'], 'checkout_commission_amount_cents')
                : null,
            checkoutTotalAmountCents: isset($payload['checkout_total_amount_cents'])
                ? Scalar::int($payload['checkout_total_amount_cents'], 'checkout_total_amount_cents')
                : null,
            checkoutNetAmountCents: isset($payload['checkout_net_amount_cents'])
                ? Scalar::int($payload['checkout_net_amount_cents'], 'checkout_net_amount_cents')
                : null,
        );
    }
}
