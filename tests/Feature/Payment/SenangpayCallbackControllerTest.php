<?php

namespace Tests\Feature\Payment;

use App\Events\PaymentCompleted;
use App\Services\SenangpaySignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Cart;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Models\TaxClass;
use Lunar\Models\Transaction;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SenangpayCallbackControllerTest extends TestCase
{
    use RefreshDatabase;

    private SenangpaySignatureService $signatureService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create default Language for Lunar PHP
        Language::factory()->create(['default' => true, 'code' => 'en']);

        // Create default TaxClass
        TaxClass::factory()->create(['default' => true, 'name' => 'Default']);

        config([
            'services.senangpay.merchant_id' => 'TEST_MERCHANT',
            'services.senangpay.secret_key' => 'test-secret-key',
            'services.senangpay.base_url' => 'https://app.senangpay.my',
            'services.senangpay.currency' => 'MYR',
        ]);

        $this->signatureService = new SenangpaySignatureService;
    }

    #[Test]
    public function return_url_captures_successful_payment()
    {
        Event::fake([PaymentCompleted::class]);

        // Mock HTTP response for payment status query
        Http::fake([
            'https://app.senangpay.my/apiv1/query_order_status' => Http::response([
                'status' => 1,
                'msg' => 'Query was successful',
                'data' => [
                    [
                        'transaction_id' => 'TXN123456',
                        'order_id' => 'ORD-2026-00001',
                        'status' => 1,
                    ],
                ],
            ]),
        ]);

        // Create test user and order
        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        // Create intent transaction
        $transaction = Transaction::create([
            'order_id' => $order->id,
            'success' => true,
            'type' => 'intent',
            'driver' => 'senangpay',
            'amount' => $order->total,
            'reference' => 'ORD-2026-00001',
            'status' => 'pending',
        ]);

        // Send return URL request
        $response = $this->get('/payment/senangpay/return?'.http_build_query([
            'status_id' => '1',
            'order_id' => 'ORD-2026-00001',
            'transaction_id' => 'TXN123456',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.processing');

        // Assert capture transaction created
        $this->assertDatabaseHas('lunar_transactions', [
            'order_id' => $order->id,
            'type' => 'capture',
            'driver' => 'senangpay',
            'reference' => 'ORD-2026-00001',
            'success' => true,
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
    public function return_url_handles_failed_payment()
    {
        Event::fake([PaymentCompleted::class]);

        // Mock HTTP response for failed payment
        Http::fake([
            'https://app.senangpay.my/apiv1/query_order_status' => Http::response([
                'status' => 1,
                'msg' => 'Query was successful',
                'data' => [
                    [
                        'transaction_id' => 'TXN123456',
                        'order_id' => 'ORD-2026-00001',
                        'status' => 0,
                    ],
                ],
            ]),
        ]);

        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        Transaction::create([
            'order_id' => $order->id,
            'success' => true,
            'type' => 'intent',
            'driver' => 'senangpay',
            'amount' => $order->total,
            'reference' => 'ORD-2026-00001',
            'status' => 'pending',
        ]);

        $response = $this->get('/payment/senangpay/return?'.http_build_query([
            'status_id' => '0',
            'order_id' => 'ORD-2026-00001',
            'transaction_id' => 'TXN123456',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.processing');

        // Assert order status updated to failed
        $order->refresh();
        $this->assertEquals('payment-failed', $order->status);

        // Assert WebSocket event with failed status
        Event::assertDispatched(PaymentCompleted::class, function ($event) {
            return $event->status === 'failed';
        });
    }

    #[Test]
    public function return_url_prevents_duplicate_capture()
    {
        // Mock HTTP response
        Http::fake([
            'https://app.senangpay.my/apiv1/query_order_status' => Http::response([
                'status' => 1,
                'msg' => 'Query was successful',
                'data' => [
                    [
                        'transaction_id' => 'TXN123456',
                        'order_id' => 'ORD-2026-00001',
                        'status' => 1,
                    ],
                ],
            ]),
        ]);

        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        $intentTransaction = Transaction::create([
            'order_id' => $order->id,
            'success' => true,
            'type' => 'intent',
            'driver' => 'senangpay',
            'amount' => $order->total,
            'reference' => 'ORD-2026-00001',
            'status' => 'pending',
        ]);

        // Create capture transaction (already processed)
        Transaction::create([
            'parent_transaction_id' => $intentTransaction->id,
            'order_id' => $order->id,
            'success' => true,
            'type' => 'capture',
            'driver' => 'senangpay',
            'amount' => $order->total,
            'reference' => 'ORD-2026-00001',
            'status' => 'captured',
        ]);

        // Send return URL request
        $response = $this->get('/payment/senangpay/return?'.http_build_query([
            'status_id' => '1',
            'order_id' => 'ORD-2026-00001',
            'transaction_id' => 'TXN123456',
        ]));

        $response->assertStatus(200);

        // Assert only ONE capture transaction exists
        $this->assertEquals(1, Transaction::where('order_id', $order->id)
            ->where('type', 'capture')
            ->count());
    }

    #[Test]
    public function return_url_shows_error_for_nonexistent_order()
    {
        $response = $this->get('/payment/senangpay/return?'.http_build_query([
            'status_id' => '1',
            'order_id' => 'NONEXISTENT',
            'transaction_id' => 'TXN123456',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.error');
        $response->assertViewHas('message', 'Order not found');
    }

    #[Test]
    public function return_url_shows_error_if_query_fails()
    {
        // Mock HTTP response for failed query
        Http::fake([
            'https://app.senangpay.my/apiv1/query_order_status' => Http::response(
                ['status' => 0, 'msg' => 'Order not found'],
                404
            ),
        ]);

        $user = \App\Models\User::factory()->create();
        $order = $this->createTestOrder($user);

        Transaction::create([
            'order_id' => $order->id,
            'success' => true,
            'type' => 'intent',
            'driver' => 'senangpay',
            'amount' => $order->total,
            'reference' => 'ORD-2026-00001',
            'status' => 'pending',
        ]);

        $response = $this->get('/payment/senangpay/return?'.http_build_query([
            'status_id' => '1',
            'order_id' => 'ORD-2026-00001',
            'transaction_id' => 'TXN123456',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('payment.error');
        $response->assertViewHas('message', 'Could not verify payment status');
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
            'meta' => ['senangpay_reference' => 'ORD-2026-00001'],
        ]);

        return $order;
    }
}
