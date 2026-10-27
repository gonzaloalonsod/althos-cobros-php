<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Dto;

final readonly class CreateCheckoutRequest
{
    /**
     * @param list<CheckoutLineRequest>                                        $lines
     * @param array{success?: string, failure?: string, pending?: string}|null $backUrls
     */
    public function __construct(
        public array $lines,
        public ?string $customerExternalId = null,
        public ?string $customerEmail = null,
        public ?string $idempotencyKey = null,
        public ?array $backUrls = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $payload = [
            'lines' => array_map(
                static fn (CheckoutLineRequest $line): array => $line->toArray(),
                $this->lines,
            ),
        ];
        if (null !== $this->customerExternalId) {
            $payload['customer_external_id'] = $this->customerExternalId;
        }
        if (null !== $this->customerEmail) {
            $payload['customer_email'] = $this->customerEmail;
        }
        if (null !== $this->idempotencyKey) {
            $payload['idempotency_key'] = $this->idempotencyKey;
        }
        if (null !== $this->backUrls) {
            $payload['back_urls'] = $this->backUrls;
        }

        return $payload;
    }
}
