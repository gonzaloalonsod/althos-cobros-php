# AlthoCobros PHP SDK

Cliente PHP tipado para la [AlthoCobros Checkout API](https://cobros.althoapp.com).

Sin dependencias de framework: **PHP 8.2+**, **ext-curl**, **ext-hash**.

- Packagist: [althosalud/cobros](https://packagist.org/packages/althosalud/cobros)
- Repo: [gonzaloalonsod/althos-cobros-php](https://github.com/gonzaloalonsod/althos-cobros-php)
- Contrato HTTP: `https://cobros.althoapp.com/api/docs` (OpenAPI). Este SDK no calcula precios.

## Instalación

```bash
composer require althosalud/cobros
```

## Uso

```php
use AlthoSalud\Cobros\CobrosClient;
use AlthoSalud\Cobros\Dto\CheckoutLineRequest;
use AlthoSalud\Cobros\Dto\CreateCheckoutRequest;
use AlthoSalud\Cobros\EntitlementSignature;

$client = new CobrosClient(
    baseUrl: CobrosClient::DEFAULT_BASE_URL, // https://cobros.althoapp.com
    apiKey: 'cb_...',
);

$me = $client->me();
// $me->commissionBps, $me->commissionMode

$checkout = $client->createCheckout(new CreateCheckoutRequest(
    lines: [new CheckoutLineRequest(
        amountCents: 15000,
        currency: 'ARS',
        description: 'Plan mensual',
        sku: 'althosalud.pro',
        periodStart: '2026-08-01',
        periodEnd: '2026-08-31',
        entitlementRef: 'org-42:2026-08',
        customerExternalId: 'org-42',
    )],
    customerExternalId: 'org-42',
    idempotencyKey: 'org-42-2026-08',
    backUrls: [
        'success' => 'https://tu-app.example/pago/ok',
        'failure' => 'https://tu-app.example/pago/error',
    ],
));

// Arrays also work for backward compatibility:
// $client->createCheckout([...], idempotencyKey: '...');

// Redirect the payer:
$checkout->initPoint;
// Commission breakdown:
// $checkout->subtotalAmountCents, $checkout->commissionAmountCents,
// $checkout->totalAmountCents, $checkout->netAmountCents

$paid = $client->getCheckout($checkout->id);
```

### Webhook `entitlement.paid`

```php
$event = EntitlementSignature::parse(
    rawBody: $request->getContent(),
    signatureHeader: (string) $request->headers->get('X-Cobros-Signature'),
    secret: $applicationWebhookSecret,
    idempotencyKey: $request->headers->get('X-Cobros-Idempotency-Key'),
);
// $event->sku, amountCents, periodStart, customerExternalId
// $event->checkoutCommissionBps, checkoutTotalAmountCents, ...
```

`withApiKey()` is immutable (one key per tenant).
