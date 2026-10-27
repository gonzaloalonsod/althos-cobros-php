<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Dto;

use AlthoSalud\Cobros\Exception\InvalidResponseException;

final readonly class Checkout
{
    /**
     * @param list<array<string, mixed>>                                       $lines
     * @param list<CheckoutLine>                                               $lineItems
     * @param array{success?: string, failure?: string, pending?: string}|null $backUrls
     */
    public function __construct(
        public string $id,
        public string $status,
        public string $currency,
        public int $totalAmountCents,
        public ?string $initPoint,
        public array $lines = [],
        public ?string $customerExternalId = null,
        public ?string $paymentId = null,
        public int $subtotalAmountCents = 0,
        public int $commissionBps = 0,
        public string $commissionMode = 'none',
        public int $commissionAmountCents = 0,
        public int $netAmountCents = 0,
        public ?string $mpPreferenceId = null,
        public ?string $paidAt = null,
        public ?string $createdAt = null,
        public ?array $backUrls = null,
        public array $lineItems = [],
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $id = $payload['id'] ?? null;
        if (!\is_string($id) || '' === $id) {
            throw InvalidResponseException::field('id', 'non-empty string');
        }

        $status = $payload['status'] ?? null;
        if (!\is_string($status) || '' === $status) {
            throw InvalidResponseException::field('status', 'non-empty string');
        }

        $currency = $payload['currency'] ?? null;
        if (!\is_string($currency) || '' === $currency) {
            throw InvalidResponseException::field('currency', 'non-empty string');
        }

        $total = Scalar::int($payload['total_amount_cents'] ?? null, 'total_amount_cents');
        $subtotal = isset($payload['subtotal_amount_cents'])
            ? Scalar::int($payload['subtotal_amount_cents'], 'subtotal_amount_cents')
            : $total;
        $commissionBps = isset($payload['commission_bps'])
            ? Scalar::int($payload['commission_bps'], 'commission_bps')
            : 0;
        $commissionAmount = isset($payload['commission_amount_cents'])
            ? Scalar::int($payload['commission_amount_cents'], 'commission_amount_cents')
            : 0;
        $net = isset($payload['net_amount_cents'])
            ? Scalar::int($payload['net_amount_cents'], 'net_amount_cents')
            : $total;
        $commissionMode = $payload['commission_mode'] ?? 'none';
        if (!\is_string($commissionMode) || '' === $commissionMode) {
            $commissionMode = 'none';
        }

        $init = $payload['init_point'] ?? null;
        $rawLines = $payload['lines'] ?? [];
        $lines = [];
        $lineItems = [];
        if (\is_array($rawLines)) {
            foreach ($rawLines as $line) {
                if (\is_array($line)) {
                    $lines[] = $line;
                    $lineItems[] = CheckoutLine::fromArray($line);
                }
            }
        }

        $customer = $payload['customer_external_id'] ?? null;
        $paymentId = $payload['payment_id'] ?? null;
        $mpPreferenceId = $payload['mp_preference_id'] ?? null;
        $paidAt = $payload['paid_at'] ?? null;
        $createdAt = $payload['created_at'] ?? null;
        $backUrls = $payload['back_urls'] ?? null;
        if (!\is_array($backUrls)) {
            $backUrls = null;
        }

        return new self(
            id: $id,
            status: $status,
            currency: $currency,
            totalAmountCents: $total,
            initPoint: \is_string($init) ? $init : null,
            lines: $lines,
            customerExternalId: \is_string($customer) ? $customer : null,
            paymentId: \is_string($paymentId) ? $paymentId : null,
            subtotalAmountCents: $subtotal,
            commissionBps: $commissionBps,
            commissionMode: $commissionMode,
            commissionAmountCents: $commissionAmount,
            netAmountCents: $net,
            mpPreferenceId: \is_string($mpPreferenceId) ? $mpPreferenceId : null,
            paidAt: \is_string($paidAt) ? $paidAt : null,
            createdAt: \is_string($createdAt) ? $createdAt : null,
            backUrls: $backUrls,
            lineItems: $lineItems,
        );
    }
}
