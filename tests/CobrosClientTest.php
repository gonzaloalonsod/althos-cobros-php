<?php

declare(strict_types=1);

namespace AlthoSalud\Cobros\Tests;

use AlthoSalud\Cobros\CobrosClient;
use AlthoSalud\Cobros\Dto\CheckoutLineRequest;
use AlthoSalud\Cobros\Dto\CreateCheckoutRequest;
use AlthoSalud\Cobros\EntitlementSignature;
use AlthoSalud\Cobros\Exception\ApiException;
use AlthoSalud\Cobros\Exception\ConfigurationException;
use AlthoSalud\Cobros\Exception\InvalidResponseException;
use AlthoSalud\Cobros\Exception\InvalidSignatureException;
use AlthoSalud\Cobros\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

final class CobrosClientTest extends TestCase
{
    public function test_me_returns_application_with_commission_fields(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'application' => [
                    'id' => 2,
                    'name' => 'AlthoSalud',
                    'slug' => 'althasalud',
                    'status' => 'active',
                    'rate_limit_per_minute' => '60',
                    'commission_bps' => 100,
                    'commission_mode' => 'surcharge',
                ],
                'api_key' => [
                    'prefix' => 'cb_abc',
                    'label' => 'prod',
                    'scopes' => ['checkouts:write', 'checkouts:read'],
                ],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient('https://cobros.example/', 'cb_secret', $transport);

        $me = $client->me();
        self::assertSame(2, $me->id);
        self::assertSame('AlthoSalud', $me->name);
        self::assertSame('althasalud', $me->slug);
        self::assertSame(60, $me->rateLimitPerMinute);
        self::assertSame(100, $me->commissionBps);
        self::assertSame('surcharge', $me->commissionMode);
        self::assertSame('cb_abc', $me->keyPrefix);
        self::assertSame('prod', $me->keyLabel);
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
                'lines' => [[
                    'amount_cents' => 15000,
                    'currency' => 'ARS',
                    'description' => 'Plan',
                    'sku' => 'plan',
                ]],
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
        $sent = json_decode((string) $transport->requests[0]['body'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame(15000, $sent['lines'][0]['amount_cents']);
        self::assertSame('idem-1', $sent['idempotency_key']);
    }

    public function test_create_checkout_accepts_typed_request_and_syncs_idempotency(): void
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
                'back_urls' => ['success' => 'https://app.example/ok'],
                'lines' => [[
                    'id' => 1,
                    'amount_cents' => 100000,
                    'currency' => 'ARS',
                    'description' => 'Plan',
                    'sku' => 'plan',
                    'target_application_slug' => 'altha',
                ]],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient('https://cobros.example', 'cb_key', $transport);
        $checkout = $client->createCheckout(new CreateCheckoutRequest(
            lines: [new CheckoutLineRequest(
                amountCents: 100000,
                currency: 'ARS',
                description: 'Plan',
                sku: 'plan',
                periodStart: '2026-08-01',
                periodEnd: '2026-08-31',
                entitlementRef: 'ref',
                targetApplicationSlug: 'altha',
            )],
            customerExternalId: 'org-1',
            idempotencyKey: 'idem-2',
            backUrls: ['success' => 'https://app.example/ok'],
        ));
        self::assertSame('chk-2', $checkout->id);
        self::assertSame(1000, $checkout->commissionAmountCents);
        self::assertSame(101000, $checkout->totalAmountCents);
        self::assertSame('surcharge', $checkout->commissionMode);
        self::assertCount(1, $checkout->lineItems);
        self::assertSame('altha', $checkout->lineItems[0]->targetApplicationSlug);
        self::assertSame('idem-2', $transport->requests[0]['headers']['Idempotency-Key']);

        $sent = json_decode((string) $transport->requests[0]['body'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('org-1', $sent['customer_external_id']);
        self::assertSame('idem-2', $sent['idempotency_key']);
        self::assertSame(['success' => 'https://app.example/ok'], $sent['back_urls']);
        self::assertSame(100000, $sent['lines'][0]['amount_cents']);
        self::assertSame('altha', $sent['lines'][0]['target_application_slug']);
    }

    public function test_create_checkout_rejects_conflicting_idempotency_keys(): void
    {
        $client = new CobrosClient('https://cobros.example', 'cb_key', new MockHttpTransport());
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Conflicting idempotency keys');
        $client->createCheckout(new CreateCheckoutRequest(
            lines: [new CheckoutLineRequest(
                amountCents: 1000,
                currency: 'ARS',
                description: 'Plan',
                sku: 'plan',
                periodStart: '2026-08-01',
                periodEnd: '2026-08-31',
                entitlementRef: 'ref',
            )],
            idempotencyKey: 'from-dto',
        ), 'from-arg');
    }

    public function test_create_checkout_method_arg_overrides_empty_body_key(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(201, json_encode([
                'id' => 'chk-3',
                'status' => 'pending',
                'currency' => 'ARS',
                'total_amount_cents' => 1000,
                'init_point' => 'https://pay.example/3',
                'lines' => [[
                    'amount_cents' => 1000,
                    'currency' => 'ARS',
                    'description' => 'Plan',
                    'sku' => 'plan',
                ]],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient('https://cobros.example', 'cb_key', $transport);
        $client->createCheckout([
            'lines' => [[
                'amount_cents' => 1000,
                'currency' => 'ARS',
                'description' => 'Plan',
                'sku' => 'plan',
                'period_start' => '2026-08-01',
                'period_end' => '2026-08-31',
                'entitlement_ref' => 'ref',
            ]],
        ], 'header-only');
        $sent = json_decode((string) $transport->requests[0]['body'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('header-only', $sent['idempotency_key']);
        self::assertSame('header-only', $transport->requests[0]['headers']['Idempotency-Key']);
    }

    public function test_default_base_url_is_canonical_source(): void
    {
        self::assertSame('https://cobros.althoapp.com', CobrosClient::DEFAULT_BASE_URL);
        self::assertSame(CobrosClient::DEFAULT_BASE_URL, CobrosClient::DEFAULT_PRODUCTION_BASE_URL);
    }

    public function test_default_base_url_trailing_slash_normalized(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'application' => ['id' => 1, 'slug' => 'app', 'status' => 'active'],
                'api_key' => ['scopes' => ['checkouts:read']],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient(CobrosClient::DEFAULT_BASE_URL.'/', 'cb_key', $transport);
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
        } catch (ApiException $exception) {
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
                'lines' => [],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient('https://cobros.example', 'cb_key', $transport);
        $checkout = $client->getCheckout('chk-1');
        self::assertSame('paid', $checkout->status);
        self::assertSame('https://cobros.example/api/v1/checkouts/chk-1', $transport->requests[0]['url']);
    }

    public function test_malformed_line_in_checkout_response_throws(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'id' => 'chk-bad',
                'status' => 'pending',
                'currency' => 'ARS',
                'total_amount_cents' => 1000,
                'init_point' => null,
                'lines' => [['amount_cents' => 1000]],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new CobrosClient('https://cobros.example', 'cb_key', $transport);
        $this->expectException(InvalidResponseException::class);
        $this->expectExceptionMessage('lines[0]');
        $client->getCheckout('chk-bad');
    }

    public function test_missing_key_throws(): void
    {
        $client = new CobrosClient('https://cobros.example', transport: new MockHttpTransport());
        $this->expectException(ConfigurationException::class);
        $client->me();
    }

    public function test_entitlement_signature_valid_with_commission_metadata(): void
    {
        $body = json_encode([
            'event' => 'entitlement.paid',
            'checkout_id' => 'c1',
            'sku' => 'plan',
            'amount_cents' => 1000,
            'currency' => 'ARS',
            'checkout_subtotal_amount_cents' => 100000,
            'checkout_commission_bps' => 100,
            'checkout_commission_mode' => 'surcharge',
            'checkout_commission_amount_cents' => 1000,
            'checkout_total_amount_cents' => 101000,
            'checkout_net_amount_cents' => 100000,
        ], \JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $body, 'secret');
        $event = EntitlementSignature::parse($body, $sig, 'secret', 'entitlement:c1:1:p1');
        self::assertSame('plan', $event->sku);
        self::assertSame('entitlement:c1:1:p1', $event->idempotencyKey);
        self::assertSame(100000, $event->checkoutSubtotalAmountCents);
        self::assertSame(100, $event->checkoutCommissionBps);
        self::assertSame('surcharge', $event->checkoutCommissionMode);
        self::assertSame(1000, $event->checkoutCommissionAmountCents);
        self::assertSame(101000, $event->checkoutTotalAmountCents);
        self::assertSame(100000, $event->checkoutNetAmountCents);
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
