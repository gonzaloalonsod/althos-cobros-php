<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Dto;

use AlthoSalud\Cobros\Exception\InvalidResponseException;

final readonly class Checkout
{
    /**
     * @param list<array<string, mixed>> $lines
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

        $init = $payload['init_point'] ?? null;
        $rawLines = $payload['lines'] ?? [];
        $lines = [];
        if (\is_array($rawLines)) {
            foreach ($rawLines as $line) {
                if (\is_array($line)) {
                    $lines[] = $line;
                }
            }
        }

        $customer = $payload['customer_external_id'] ?? null;
        $paymentId = $payload['payment_id'] ?? null;

        return new self(
            id: $id,
            status: $status,
            currency: $currency,
            totalAmountCents: $total,
            initPoint: \is_string($init) ? $init : null,
            lines: $lines,
            customerExternalId: \is_string($customer) ? $customer : null,
            paymentId: \is_string($paymentId) ? $paymentId : null,
        );
    }
}
