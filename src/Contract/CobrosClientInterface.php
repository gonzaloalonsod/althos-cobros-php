<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Contract;

use AlthoSalud\Cobros\Dto\ApplicationCredentials;
use AlthoSalud\Cobros\Dto\Checkout;
use AlthoSalud\Cobros\Dto\CreateCheckoutRequest;
use AlthoSalud\Cobros\Dto\EntitlementPaid;

interface CobrosClientInterface
{
    public function isConfigured(): bool;

    public function withApiKey(string $apiKey): self;

    public function me(): ApplicationCredentials;

    /**
     * @param array<string, mixed>|CreateCheckoutRequest $payload
     */
    public function createCheckout(array|CreateCheckoutRequest $payload, ?string $idempotencyKey = null): Checkout;

    public function getCheckout(string $id): Checkout;

    public function verifyEntitlementSignature(string $rawBody, string $signatureHeader, string $secret, ?string $idempotencyKey = null): EntitlementPaid;
}
