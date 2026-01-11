<?php

namespace Tests\Feature\Payment;

use App\Events\PaymentCompleted;
use App\Services\RevpaySignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Lunar\Models\Cart;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Models\TaxClass;
use Lunar\Models\Transaction;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevpayCallbackControllerTest extends TestCase
{
    use RefreshDatabase;

    private RevpaySignatureService $signatureService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create default Language for Lunar PHP
        \Lunar\Models\Language::factory()->create(['default' => true, 'code' => 'en']);
        
        // Create default TaxClass
        \Lunar\Models\TaxClass::factory()->create(['default' => true, 'name' => 'Default']);

        config([
            'services.revpay.merchant_id' => 'TEST_MERCHANT',
            'services.revpay.merchant_key' => 'test-secret-key',
            'services.revpay.key_index' => 1,
            'services.revpay.base_url' => 'https://test.revpay.com/v1',
            'services.revpay.currency' => 'MYR',
        ]);

        $this->signatureService = new RevpaySignatureService();
    }

    #[Test]
    public function backend_callback_captures_successful_payment()
    {
        Event::fake([PaymentCompleted::class]);

        // Create test user and order
        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        // Create intent transaction
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'success' => true,
            'type' => 'intent',
            'driver' => 'revpay',
            'amount' => $order->total,
            'reference' => 'ORD-2026-00001',
            'status' => 'pending',
            'card_type' => '',
            'last_four' => '',
        ]);

        // Generate valid signature
        $signature = $this->signatureService->generateResponseSignature(
            'test-secret-key',
            'TEST_MERCHANT',
            'TXN123456',
            '00',
            'ORD-2026-00001',
            '100.00',
            'MYR'
        );

        // Send backend callback
        $response = $this->post('/payment/revpay/callback', [
            'Transaction_ID' => 'TXN123456',
            'Reference_Number' => 'ORD-2026-00001',
            'Response_Code' => '00',
            'Amount' => '100.00',
            'Currency' => 'MYR',
            'Signature' => $signature,
            'Card_Type' => 'Visa',
            'Last_Four' => '4242',
        ]);

        $response->assertStatus(200);
        $response->assertSeeText('OK');

        // Assert capture transaction created
        $this->assertDatabaseHas('lunar_transactions', [
            'order_id' => $order->id,
            'type' => 'capture',
            'driver' => 'revpay',
            'reference' => 'ORD-2026-00001',
            'success' => true,
            'card_type' => 'Visa',
            'last_four' => '4242',
        ]);

        // Assert order status updated
        $order->refresh();
        $this->assertEquals('payment-received', $order->status);

        // Assert WebSocket event broadcasted
        Event::assertDispatched(PaymentCompleted::class, function ($event) use ($user, $order) {
            return $event->userId === $user->id
                && $event->referenceNumber === 'ORD-2026-00001'
                && $event->status === 'success'
                && $event->orderId === $order->id;
        });
    }

    #[Test]
    public function backend_callback_rejects_invalid_signature()
    {
        Event::fake([PaymentCompleted::class]);

        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        Transaction::create([
            'order_id' => $order->id,
            'success' => true,
            'type' => 'intent',
            'driver' => 'revpay',
            'amount' => $order->total,
            'reference' => 'ORD-2026-00001',
            'status' => 'pending',
            'card_type' => '',
            'last_four' => '',
        ]);

        // Send backend callback with invalid signature
        $response = $this->post('/payment/revpay/callback', [
            'Transaction_ID' => 'TXN123456',
            'Reference_Number' => 'ORD-2026-00001',
            'Response_Code' => '00',
            'Amount' => '100.00',
            'Currency' => 'MYR',
            'Signature' => 'invalid-signature',
            'Card_Type' => 'Visa',
            'Last_Four' => '4242',
        ]);

        $response->assertStatus(400);

        // Assert NO capture transaction created
        $this->assertDatabaseMissing('lunar_transactions', [
            'order_id' => $order->id,
            'type' => 'capture',
        ]);

        // Assert NO WebSocket event
        Event::assertNotDispatched(PaymentCompleted::class);
    }

    #[Test]
    public function backend_callback_handles_failed_payment()
    {
        Event::fake([PaymentCompleted::class]);

        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        Transaction::create([
            'order_id' => $order->id,
            'success' => true,
            'type' => 'intent',
            'driver' => 'revpay',
            'amount' => $order->total,
            'reference' => 'ORD-2026-00001',
            'status' => 'pending',
            'card_type' => '',
            'last_four' => '',
        ]);

        // Generate valid signature for failed payment
        $signature = $this->signatureService->generateResponseSignature(
            'test-secret-key',
            'TEST_MERCHANT',
            'TXN123456',
            '99', // Failed response code
            'ORD-2026-00001',
            '100.00',
            'MYR'
        );

        $response = $this->post('/payment/revpay/callback', [
            'Transaction_ID' => 'TXN123456',
            'Reference_Number' => 'ORD-2026-00001',
            'Response_Code' => '99',
            'Amount' => '100.00',
            'Currency' => 'MYR',
            'Signature' => $signature,
            'Response_Message' => 'Insufficient funds',
        ]);

        $response->assertStatus(200);
        $response->assertSeeText('OK');

        // Assert order status updated to failed
        $order->refresh();
        $this->assertEquals('payment-failed', $order->status);

        // Assert WebSocket event with failed status
        Event::assertDispatched(PaymentCompleted::class, function ($event) {
            return $event->status === 'failed';
        });
    }

    #[Test]
    public function backend_callback_prevents_duplicate_processing()
    {
        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        $intentTransaction = Transaction::create([
            'order_id' => $order->id,
            'success' => true,
            'type' => 'intent',
            'driver' => 'revpay',
            'amount' => $order->total,
            'reference' => 'ORD-2026-00001',
            'status' => 'pending',
            'card_type' => '',
            'last_four' => '',
        ]);

        // Create capture transaction (already processed)
        Transaction::create([
            'parent_transaction_id' => $intentTransaction->id,
            'order_id' => $order->id,
            'success' => true,
            'type' => 'capture',
            'driver' => 'revpay',
            'amount' => $order->total,
            'reference' => 'ORD-2026-00001',
            'status' => 'captured',
            'card_type' => 'Visa',
            'last_four' => '1234',
        ]);

        $signature = $this->signatureService->generateResponseSignature(
            'test-secret-key',
            'TEST_MERCHANT',
            'TXN123456',
            '00',
            'ORD-2026-00001',
            '100.00',
            'MYR'
        );

        // Send duplicate callback
        $response = $this->post('/payment/revpay/callback', [
            'Transaction_ID' => 'TXN123456',
            'Reference_Number' => 'ORD-2026-00001',
            'Response_Code' => '00',
            'Amount' => '100.00',
            'Currency' => 'MYR',
            'Signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertSeeText('OK');

        // Assert only ONE capture transaction exists
        $this->assertEquals(1, Transaction::where('order_id', $order->id)
            ->where('type', 'capture')
            ->count());
    }

    #[Test]
    public function return_url_broadcasts_websocket_event()
    {
        Event::fake([PaymentCompleted::class]);

        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        $signature = $this->signatureService->generateResponseSignature(
            'test-secret-key',
            'TEST_MERCHANT',
            'TXN123456',
            '00',
            'ORD-2026-00001',
            '100.00',
            'MYR'
        );

        $response = $this->get('/payment/revpay/return?'.http_build_query([
            'Transaction_ID' => 'TXN123456',
            'Reference_Number' => 'ORD-2026-00001',
            'Response_Code' => '00',
            'Amount' => '100.00',
            'Currency' => 'MYR',
            'Signature' => $signature,
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.processing');

        // Assert WebSocket event broadcasted
        Event::assertDispatched(PaymentCompleted::class, function ($event) use ($user) {
            return $event->userId === $user->id
                && $event->status === 'success';
        });
    }

    #[Test]
    public function return_url_handles_bank_redirect_response_code_09()
    {
        Event::fake([PaymentCompleted::class]);

        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        $signature = $this->signatureService->generateResponseSignature(
            'test-secret-key',
            'TEST_MERCHANT',
            'TXN123456',
            '09',
            'ORD-2026-00001',
            '100.00',
            'MYR'
        );

        $response = $this->get('/payment/revpay/return?'.http_build_query([
            'Transaction_ID' => 'TXN123456',
            'Reference_Number' => 'ORD-2026-00001',
            'Response_Code' => '09',
            'Amount' => '100.00',
            'Currency' => 'MYR',
            'Signature' => $signature,
            'Bank_Redirect_URL' => 'https://bank.example.com/3dsecure',
        ]));

        // Should redirect to bank URL
        $response->assertRedirect('https://bank.example.com/3dsecure');

        // Should broadcast redirect_required status
        Event::assertDispatched(PaymentCompleted::class, function ($event) {
            return $event->status === 'redirect_required';
        });
    }

    #[Test]
    public function return_url_shows_error_for_invalid_signature()
    {
        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        $response = $this->get('/payment/revpay/return?'.http_build_query([
            'Transaction_ID' => 'TXN123456',
            'Reference_Number' => 'ORD-2026-00001',
            'Response_Code' => '00',
            'Amount' => '100.00',
            'Currency' => 'MYR',
            'Signature' => 'invalid-signature',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.error');
    }

    private function createTestOrder(\App\Models\User $user): Order
    {
        $currency = Currency::firstOrCreate(
            ['code' => 'MYR'],
            ['name' => 'Malaysian Ringgit', 'default' => true, 'decimal_places' => 2, 'enabled' => true, 'exchange_rate' => 1.00]
        );
        $channel = Channel::firstOrCreate(
            ['default' => true],
            ['name' => 'Default', 'handle' => 'default', 'url' => 'http://localhost']
        );

        $cart = Cart::create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'channel_id' => $channel->id,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'currency_code' => $currency->code,
            'channel_id' => $channel->id,
            'status' => 'payment-pending',
            'total' => 10000, // 100.00 in cents
            'meta' => ['revpay_reference' => 'ORD-2026-00001'],
        ]);

        return $order;
    }
}
