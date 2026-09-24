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
use AlthoSalud\Cobros\EntitlementSignature;

$client = new CobrosClient(
    baseUrl: CobrosClient::DEFAULT_PRODUCTION_BASE_URL,
    apiKey: 'cb_...',
);

$me = $client->me();

$checkout = $client->createCheckout([
    'customer_external_id' => 'org-42',
    'lines' => [[
        'amount_cents' => 15000,
        'currency' => 'ARS',
        'description' => 'Plan mensual',
        'sku' => 'althasalud.pro',
        'period_start' => '2026-08-01',
        'period_end' => '2026-08-31',
        'entitlement_ref' => 'org-42:2026-08',
    ]],
], idempotencyKey: 'org-42-2026-08');

// Redirect the payer:
$checkout->initPoint;

$paid = $client->getCheckout($checkout->id);
```

`baseUrl` acepta con o sin barra final (`https://cobros.althoapp.com` y `https://cobros.althoapp.com/` son equivalentes).

### Webhook `entitlement.paid`

```php
$event = EntitlementSignature::parse(
    rawBody: $request->getContent(),
    signatureHeader: (string) $request->headers->get('X-Cobros-Signature'),
    secret: $applicationWebhookSecret,
    idempotencyKey: $request->headers->get('X-Cobros-Idempotency-Key'),
);
// $event->sku, amountCents, periodStart, customerExternalId
```

`withApiKey()` is immutable (one key per tenant).
