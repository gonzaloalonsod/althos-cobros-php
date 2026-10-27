<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Dto;

final readonly class CheckoutLineRequest
{
    public function __construct(
        public int $amountCents,
        public string $currency,
        public string $description,
        public string $sku,
        public string $periodStart,
        public string $periodEnd,
        public string $entitlementRef,
        public ?string $customerExternalId = null,
        public ?string $entitlementTargetUrl = null,
        public ?string $targetApplicationSlug = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $payload = [
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
            'description' => $this->description,
            'sku' => $this->sku,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'entitlement_ref' => $this->entitlementRef,
        ];
        if (null !== $this->customerExternalId) {
            $payload['customer_external_id'] = $this->customerExternalId;
        }
        if (null !== $this->entitlementTargetUrl) {
            $payload['entitlement_target_url'] = $this->entitlementTargetUrl;
        }
        if (null !== $this->targetApplicationSlug) {
            $payload['target_application_slug'] = $this->targetApplicationSlug;
        }

        return $payload;
    }
}
