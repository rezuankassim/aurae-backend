<?php

namespace Tests\Unit\Services;

use App\Services\SenangpaySignatureService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SenangpaySignatureServiceTest extends TestCase
{
    private SenangpaySignatureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SenangpaySignatureService;
    }

    #[Test]
    public function it_generates_query_order_signature_correctly()
    {
        $signature = $this->service->generateQueryOrderSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            orderId: 'ORD-2026-00001'
        );

        // Should be lowercase SHA256
        $this->assertIsString($signature);
        $this->assertEquals(64, strlen($signature)); // SHA256 produces 64 hex characters
        $this->assertEquals(strtolower($signature), $signature);
    }

    #[Test]
    public function it_generates_consistent_signatures_for_same_input()
    {
        $signature1 = $this->service->generateQueryOrderSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            orderId: 'ORD-2026-00001'
        );

        $signature2 = $this->service->generateQueryOrderSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            orderId: 'ORD-2026-00001'
        );

        $this->assertEquals($signature1, $signature2);
    }

    #[Test]
    public function it_generates_different_signatures_for_different_inputs()
    {
        $signature1 = $this->service->generateQueryOrderSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            orderId: 'ORD-2026-00001'
        );

        $signature2 = $this->service->generateQueryOrderSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            orderId: 'ORD-2026-00002' // Different order ID
        );

        $this->assertNotEquals($signature1, $signature2);
    }

    #[Test]
    public function it_generates_query_transaction_signature_correctly()
    {
        $signature = $this->service->generateQueryTransactionSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            transactionReference: 'TXN123456'
        );

        $this->assertIsString($signature);
        $this->assertEquals(64, strlen($signature));
        $this->assertEquals(strtolower($signature), $signature);
    }

    #[Test]
    public function it_generates_query_list_signature_correctly()
    {
        $signature = $this->service->generateQueryListSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            timestampStart: 1704067200,
            timestampEnd: 1704153600
        );

        $this->assertIsString($signature);
        $this->assertEquals(64, strlen($signature));
        $this->assertEquals(strtolower($signature), $signature);
    }

    #[Test]
    public function it_verifies_matching_signatures()
    {
        $signature1 = $this->service->generateQueryOrderSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            orderId: 'ORD-2026-00001'
        );

        $signature2 = $this->service->generateQueryOrderSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            orderId: 'ORD-2026-00001'
        );

        $this->assertTrue($this->service->verifySignature($signature1, $signature2));
    }

    #[Test]
    public function it_rejects_non_matching_signatures()
    {
        $signature1 = $this->service->generateQueryOrderSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            orderId: 'ORD-2026-00001'
        );

        $signature2 = 'invalid-signature';

        $this->assertFalse($this->service->verifySignature($signature1, $signature2));
    }

    #[Test]
    public function it_verifies_signatures_case_sensitively()
    {
        $signature = $this->service->generateQueryOrderSignature(
            merchantId: 'MERCHANT123',
            secretKey: 'test-secret-key',
            orderId: 'ORD-2026-00001'
        );

        $uppercaseSignature = strtoupper($signature);

        // SHA256 comparison should be case-sensitive with hash_equals
        $this->assertFalse($this->service->verifySignature($signature, $uppercaseSignature));
    }

    #[Test]
    public function it_formats_amount_to_cents()
    {
        $cents = $this->service->formatAmount(100.00);

        $this->assertEquals(10000, $cents);
        $this->assertIsInt($cents);
    }

    #[Test]
    public function it_formats_decimal_amount_to_cents()
    {
        $cents = $this->service->formatAmount(99.99);

        $this->assertEquals(9999, $cents);
    }

    #[Test]
    public function it_formats_integer_amount_to_cents()
    {
        $cents = $this->service->formatAmount(50);

        $this->assertEquals(5000, $cents);
    }

    #[Test]
    public function it_rounds_amount_correctly()
    {
        $cents = $this->service->formatAmount(99.999);

        $this->assertEquals(10000, $cents);
    }

    #[Test]
    public function it_formats_zero_amount_correctly()
    {
        $cents = $this->service->formatAmount(0);

        $this->assertEquals(0, $cents);
    }

    #[Test]
    public function it_converts_cents_back_to_decimal()
    {
        $amount = $this->service->formatAmountFromCents(10000);

        $this->assertEquals(100.00, $amount);
    }

    #[Test]
    public function it_converts_decimal_cents_back_correctly()
    {
        $amount = $this->service->formatAmountFromCents(9999);

        $this->assertEquals(99.99, $amount);
    }

    #[Test]
    public function it_handles_small_amounts_correctly()
    {
        $cents = $this->service->formatAmount(0.50);
        $this->assertEquals(50, $cents);

        $amount = $this->service->formatAmountFromCents(50);
        $this->assertEquals(0.50, $amount);
    }
}
