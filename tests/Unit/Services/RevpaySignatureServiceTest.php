<?php

namespace Tests\Unit\Services;

use App\Services\RevpaySignatureService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevpaySignatureServiceTest extends TestCase
{
    private RevpaySignatureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RevpaySignatureService;
    }

    #[Test]
    public function it_generates_payment_signature_correctly()
    {
        $signature = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00001',
            amount: '100.00',
            currency: 'MYR'
        );

        // Should be lowercase SHA-512
        $this->assertIsString($signature);
        $this->assertEquals(128, strlen($signature)); // SHA-512 produces 128 hex characters
        $this->assertEquals(strtolower($signature), $signature);
    }

    #[Test]
    public function it_generates_consistent_signatures_for_same_input()
    {
        $signature1 = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00001',
            amount: '100.00',
            currency: 'MYR'
        );

        $signature2 = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00001',
            amount: '100.00',
            currency: 'MYR'
        );

        $this->assertEquals($signature1, $signature2);
    }

    #[Test]
    public function it_generates_different_signatures_for_different_inputs()
    {
        $signature1 = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00001',
            amount: '100.00',
            currency: 'MYR'
        );

        $signature2 = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00002', // Different reference
            amount: '100.00',
            currency: 'MYR'
        );

        $this->assertNotEquals($signature1, $signature2);
    }

    #[Test]
    public function it_generates_response_signature_correctly()
    {
        $signature = $this->service->generateResponseSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            transactionId: 'TXN123456',
            responseCode: '00',
            referenceNumber: 'ORD-2026-00001',
            amount: '100.00',
            currency: 'MYR'
        );

        $this->assertIsString($signature);
        $this->assertEquals(128, strlen($signature));
        $this->assertEquals(strtolower($signature), $signature);
    }

    #[Test]
    public function it_generates_refund_signature_correctly()
    {
        $signature = $this->service->generateRefundSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'REF-2026-00001',
            refundAmount: '50.00',
            originalReferenceNumber: 'ORD-2026-00001'
        );

        $this->assertIsString($signature);
        $this->assertEquals(128, strlen($signature));
        $this->assertEquals(strtolower($signature), $signature);
    }

    #[Test]
    public function it_verifies_matching_signatures()
    {
        $signature1 = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00001',
            amount: '100.00',
            currency: 'MYR'
        );

        $signature2 = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00001',
            amount: '100.00',
            currency: 'MYR'
        );

        $this->assertTrue($this->service->verifySignature($signature1, $signature2));
    }

    #[Test]
    public function it_rejects_non_matching_signatures()
    {
        $signature1 = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00001',
            amount: '100.00',
            currency: 'MYR'
        );

        $signature2 = 'invalid-signature';

        $this->assertFalse($this->service->verifySignature($signature1, $signature2));
    }

    #[Test]
    public function it_verifies_signatures_case_insensitively()
    {
        $signature = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00001',
            amount: '100.00',
            currency: 'MYR'
        );

        $uppercaseSignature = strtoupper($signature);

        $this->assertTrue($this->service->verifySignature($signature, $uppercaseSignature));
    }

    #[Test]
    public function it_formats_integer_amount_to_two_decimals()
    {
        $formatted = $this->service->formatAmount(100);

        $this->assertEquals('100.00', $formatted);
    }

    #[Test]
    public function it_formats_float_amount_to_two_decimals()
    {
        $formatted = $this->service->formatAmount(99.9);

        $this->assertEquals('99.90', $formatted);
    }

    #[Test]
    public function it_formats_amount_with_more_decimals_to_two_decimals()
    {
        $formatted = $this->service->formatAmount(99.999);

        $this->assertEquals('100.00', $formatted);
    }

    #[Test]
    public function it_formats_zero_amount_correctly()
    {
        $formatted = $this->service->formatAmount(0);

        $this->assertEquals('0.00', $formatted);
    }

    #[Test]
    public function signature_is_sensitive_to_amount_format()
    {
        // Amount must be formatted exactly the same way
        $signature1 = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00001',
            amount: '100.00',
            currency: 'MYR'
        );

        $signature2 = $this->service->generatePaymentSignature(
            merchantKey: 'test-key',
            merchantId: 'MERCHANT123',
            referenceNumber: 'ORD-2026-00001',
            amount: '100',
            currency: 'MYR'
        );

        // Different formatting should produce different signatures
        $this->assertNotEquals($signature1, $signature2);
    }
}
