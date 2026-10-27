<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Tests;

use AlthoSalud\Cobros\CobrosClient;
use AlthoSalud\Cobros\EntitlementSignature;
use AlthoSalud\Cobros\Exception\ConfigurationException;
use AlthoSalud\Cobros\Exception\InvalidSignatureException;
use AlthoSalud\Cobros\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

final class CobrosClientTest extends TestCase
{
    public function test_me_returns_application(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'application' => [
                    'id' => 2,
                    'slug' => 'althasalud',
                    'status' => 'active',
                ],
                'api_key' => [
                    'scopes' => ['checkouts:write', 'checkouts:read'],
                ],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient('https://cobros.example/', 'cb_secret', $transport);

        $me = $client->me();
        self::assertSame(2, $me->id);
        self::assertSame('althasalud', $me->slug);
        self::assertSame(['checkouts:write', 'checkouts:read'], $me->scopes);
        self::assertSame('https://cobros.example/api/v1/me', $transport->requests[0]['url']);
    }

    public function test_create_checkout_sends_amounts_unchanged(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(201, json_encode([
                'id' => 'chk-1',
                'status' => 'pending',
                'currency' => 'ARS',
                'total_amount_cents' => 15000,
                'init_point' => 'https://pay.example/1',
                'lines' => [['amount_cents' => 15000, 'sku' => 'plan']],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient('https://cobros.example', 'cb_key', $transport);
        $payload = [
            'customer_external_id' => 'org-1',
            'lines' => [[
                'amount_cents' => 15000,
                'currency' => 'ARS',
                'description' => 'Plan',
                'sku' => 'plan',
                'period_start' => '2026-08-01',
                'period_end' => '2026-08-31',
                'entitlement_ref' => 'ref',
            ]],
        ];

        $checkout = $client->createCheckout($payload, 'idem-1');
        self::assertSame('chk-1', $checkout->id);
        self::assertSame(15000, $checkout->totalAmountCents);
        self::assertSame(15000, $checkout->subtotalAmountCents);
        self::assertSame('https://pay.example/1', $checkout->initPoint);
        self::assertSame('idem-1', $transport->requests[0]['headers']['Idempotency-Key']);
        self::assertSame($payload['lines'][0]['amount_cents'], json_decode((string) $transport->requests[0]['body'], true)['lines'][0]['amount_cents']);
    }

    public function test_create_checkout_accepts_typed_request(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(201, json_encode([
                'id' => 'chk-2',
                'status' => 'pending',
                'currency' => 'ARS',
                'subtotal_amount_cents' => 100000,
                'commission_bps' => 100,
                'commission_mode' => 'surcharge',
                'commission_amount_cents' => 1000,
                'total_amount_cents' => 101000,
                'net_amount_cents' => 100000,
                'init_point' => 'https://pay.example/2',
                'lines' => [[
                    'id' => 1,
                    'amount_cents' => 100000,
                    'currency' => 'ARS',
                    'description' => 'Plan',
                    'sku' => 'plan',
                ]],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient('https://cobros.example', 'cb_key', $transport);
        $checkout = $client->createCheckout(new \AlthoSalud\Cobros\Dto\CreateCheckoutRequest(
            lines: [new \AlthoSalud\Cobros\Dto\CheckoutLineRequest(
                amountCents: 100000,
                currency: 'ARS',
                description: 'Plan',
                sku: 'plan',
                periodStart: '2026-08-01',
                periodEnd: '2026-08-31',
                entitlementRef: 'ref',
            )],
            customerExternalId: 'org-1',
            idempotencyKey: 'idem-2',
        ));
        self::assertSame('chk-2', $checkout->id);
        self::assertSame(1000, $checkout->commissionAmountCents);
        self::assertSame(101000, $checkout->totalAmountCents);
        self::assertSame('surcharge', $checkout->commissionMode);
        self::assertCount(1, $checkout->lineItems);
        self::assertSame('idem-2', $transport->requests[0]['headers']['Idempotency-Key']);
    }

    public function test_default_base_url_constant(): void
    {
        self::assertSame('https://cobros.althoapp.com', CobrosClient::DEFAULT_BASE_URL);
        self::assertSame(CobrosClient::DEFAULT_BASE_URL, CobrosClient::DEFAULT_PRODUCTION_BASE_URL);
    }

    public function test_canonical_production_base_url_trailing_slash(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'application' => ['id' => 1, 'slug' => 'app', 'status' => 'active'],
                'api_key' => ['scopes' => ['checkouts:read']],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient(CobrosClient::DEFAULT_PRODUCTION_BASE_URL.'/', 'cb_key', $transport);
        $client->me();
        self::assertSame('https://cobros.althoapp.com/api/v1/me', $transport->requests[0]['url']);
    }

    public function test_http_response_exposes_headers(): void
    {
        $response = new HttpResponse(429, '{}', [
            'retry-after' => ['15'],
            'x-request-id' => ['req-123'],
        ]);
        self::assertSame('15', $response->header('Retry-After'));
        self::assertSame('req-123', $response->header('X-Request-Id'));
    }

    public function test_api_exception_exposes_response_headers(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(429, json_encode([
                'error' => 'rate_limit_exceeded',
                'message' => 'Too many requests',
            ], \JSON_THROW_ON_ERROR), [
                'retry-after' => ['15'],
                'x-request-id' => ['req-123'],
            ]),
        ]);
        $client = new CobrosClient('https://cobros.example', 'cb_key', $transport);
        try {
            $client->me();
            self::fail('Expected ApiException');
        } catch (\AlthoSalud\Cobros\Exception\ApiException $exception) {
            self::assertSame(429, $exception->statusCode);
            self::assertSame('15', $exception->responseHeader('Retry-After'));
            self::assertSame('req-123', $exception->responseHeader('X-Request-Id'));
        }
    }

    public function test_get_checkout(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'id' => 'chk-1',
                'status' => 'paid',
                'currency' => 'ARS',
                'total_amount_cents' => 1000,
                'init_point' => null,
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient('https://cobros.example', 'cb_key', $transport);
        $checkout = $client->getCheckout('chk-1');
        self::assertSame('paid', $checkout->status);
        self::assertSame('https://cobros.example/api/v1/checkouts/chk-1', $transport->requests[0]['url']);
    }

    public function test_missing_key_throws(): void
    {
        $client = new CobrosClient('https://cobros.example', transport: new MockHttpTransport());
        $this->expectException(ConfigurationException::class);
        $client->me();
    }

    public function test_entitlement_signature_valid(): void
    {
        $body = json_encode([
            'event' => 'entitlement.paid',
            'checkout_id' => 'c1',
            'sku' => 'plan',
            'amount_cents' => 1000,
            'currency' => 'ARS',
        ], \JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $body, 'secret');
        $event = EntitlementSignature::parse($body, $sig, 'secret', 'entitlement:c1:1:p1');
        self::assertSame('plan', $event->sku);
        self::assertSame('entitlement:c1:1:p1', $event->idempotencyKey);
    }

    public function test_entitlement_signature_accepts_sha256_prefix_and_string_amount(): void
    {
        $body = json_encode([
            'event' => 'entitlement.paid',
            'checkout_id' => 'c1',
            'sku' => 'plan',
            'amount_cents' => '1000',
            'currency' => 'ARS',
        ], \JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $body, 'secret');
        $event = EntitlementSignature::parse($body, 'sha256='.$sig, 'secret');
        self::assertSame(1000, $event->amountCents);
    }

    public function test_entitlement_signature_rejects_mismatch(): void
    {
        $this->expectException(InvalidSignatureException::class);
        EntitlementSignature::parse('{"event":"entitlement.paid"}', 'nope', 'secret');
    }
}
