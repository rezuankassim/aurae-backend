<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\SenangpaySignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lunar\Facades\Payments;
use Lunar\Models\Cart;
use Lunar\Models\Order;
use Lunar\Models\Transaction;
use Lunar\Shipping\DataTransferObjects\ShippingOptionLookup;
use Lunar\Shipping\Facades\Shipping;

class CheckoutController extends Controller
{
    private function scopeCartToSelected(Cart $cart): Cart
    {
        $selectedLines = $cart->lines()->where('selected', true)->with([
            'purchasable.taxClass',
            'purchasable.prices.currency',
            'purchasable.prices.priceable',
            'purchasable.product',
        ])->get();

        $cart->setRelation('lines', $selectedLines);

        return $cart;
    }

    public function getShippingOptions(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (! $cart) {
            return response()->json([
                'status' => 404,
                'message' => 'Cart not found.',
                'data' => null,
            ], 404);
        }

        if (! $cart->shippingAddress) {
            return response()->json([
                'status' => 400,
                'message' => 'Please set shipping address first.',
                'data' => null,
            ], 400);
        }

        if (! $cart->lines()->where('selected', true)->exists()) {
            return response()->json([
                'status' => 400,
                'message' => 'No items selected for checkout.',
                'data' => null,
            ], 400);
        }

        $this->scopeCartToSelected($cart);
        $cart->calculate();

        $shippingRates = Shipping::shippingRates($cart)->get();

        $shippingOptions = Shipping::shippingOptions($cart)->get(
            new ShippingOptionLookup(
                shippingRates: $shippingRates
            )
        );

        $options = [];

        foreach ($shippingOptions as $shippingOption) {
            $option = $shippingOption->option;
            $options[] = [
                'identifier' => $option->getIdentifier(),
                'name' => $option->getName(),
                'description' => $option->getDescription(),
                'price' => [
                    'value' => $option->getPrice()->value,
                    'formatted' => $option->getPrice()->formatted,
                ],
                'is_collection' => $option->collect,
            ];
        }

        return response()->json([
            'status' => 200,
            'message' => 'Shipping options retrieved successfully.',
            'data' => [
                'options' => $options,
                'selected_option' => $cart->shippingOptionOverride?->getIdentifier(),
            ],
        ]);
    }

    public function setShippingOption(Request $request)
    {
        $validated = $request->validate([
            'shipping_option' => ['required', 'string'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (! $cart) {
            return response()->json([
                'status' => 404,
                'message' => 'Cart not found.',
                'data' => null,
            ], 404);
        }

        if (! $cart->shippingAddress) {
            return response()->json([
                'status' => 400,
                'message' => 'Please set shipping address first.',
                'data' => null,
            ], 400);
        }

        if (! $cart->lines()->where('selected', true)->exists()) {
            return response()->json([
                'status' => 400,
                'message' => 'No items selected for checkout.',
                'data' => null,
            ], 400);
        }

        $this->scopeCartToSelected($cart);
        $cart->calculate();

        $shippingRates = Shipping::shippingRates($cart)->get();
        $shippingOptions = Shipping::shippingOptions($cart)->get(
            new ShippingOptionLookup(
                shippingRates: $shippingRates
            )
        );

        $shippingOption = null;
        foreach ($shippingOptions as $optionResult) {
            if ($optionResult->option->getIdentifier() === $validated['shipping_option']) {
                $shippingOption = $optionResult->option;
                break;
            }
        }

        if (! $shippingOption) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid shipping option.',
                'data' => null,
            ], 400);
        }

        $cart->setShippingOption($shippingOption);

        $cart->shippingBreakdown = null;

        $this->scopeCartToSelected($cart);
        $cart->recalculate();

        return response()->json([
            'status' => 200,
            'message' => 'Shipping option set successfully.',
            'data' => [
                'cart_id' => $cart->id,
                'shipping_option' => [
                    'identifier' => $shippingOption->getIdentifier(),
                    'name' => $shippingOption->getName(),
                    'description' => $shippingOption->getDescription(),
                    'price' => [
                        'value' => $shippingOption->getPrice()->value,
                        'formatted' => $shippingOption->getPrice()->formatted,
                    ],
                ],
                'cart_total' => $cart->total->formatted,
                'shipping_total' => $cart->shippingTotal?->formatted,
            ],
        ]);
    }

    public function setAddresses(Request $request)
    {
        $validated = $request->validate([
            'shipping_address' => ['required', 'array'],
            'shipping_address.title' => ['nullable', 'string', 'max:255'],
            'shipping_address.first_name' => ['required', 'string', 'max:255'],
            'shipping_address.last_name' => ['required', 'string', 'max:255'],
            'shipping_address.company_name' => ['nullable', 'string', 'max:255'],
            'shipping_address.line_one' => ['required', 'string', 'max:255'],
            'shipping_address.line_two' => ['nullable', 'string', 'max:255'],
            'shipping_address.line_three' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.state' => ['nullable', 'string', 'max:255'],
            'shipping_address.postcode' => ['required', 'string', 'max:255'],
            'shipping_address.country_id' => ['required', 'exists:lunar_countries,id'],
            'shipping_address.delivery_instructions' => ['nullable', 'string'],
            'shipping_address.contact_email' => ['required', 'email', 'max:255'],
            'shipping_address.contact_phone' => ['required', 'string', 'max:255'],
            'billing_same_as_shipping' => ['boolean'],
            'billing_address' => ['required_if:billing_same_as_shipping,false', 'array'],
            'billing_address.title' => ['nullable', 'string', 'max:255'],
            'billing_address.first_name' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.last_name' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.company_name' => ['nullable', 'string', 'max:255'],
            'billing_address.line_one' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.line_two' => ['nullable', 'string', 'max:255'],
            'billing_address.line_three' => ['nullable', 'string', 'max:255'],
            'billing_address.city' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.state' => ['nullable', 'string', 'max:255'],
            'billing_address.postcode' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing_address.country_id' => ['required_if:billing_same_as_shipping,false', 'exists:lunar_countries,id'],
            'billing_address.delivery_instructions' => ['nullable', 'string'],
            'billing_address.contact_email' => ['nullable', 'email', 'max:255'],
            'billing_address.contact_phone' => ['nullable', 'string', 'max:255'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (! $cart) {
            return response()->json([
                'status' => 404,
                'message' => 'Cart not found.',
                'data' => null,
            ], 404);
        }

        $cart->shippingAddress()->updateOrCreate(
            ['cart_id' => $cart->id, 'type' => 'shipping'],
            [
                'title' => $validated['shipping_address']['title'] ?? null,
                'first_name' => $validated['shipping_address']['first_name'],
                'last_name' => $validated['shipping_address']['last_name'],
                'company_name' => $validated['shipping_address']['company_name'] ?? null,
                'line_one' => $validated['shipping_address']['line_one'],
                'line_two' => $validated['shipping_address']['line_two'] ?? null,
                'line_three' => $validated['shipping_address']['line_three'] ?? null,
                'city' => $validated['shipping_address']['city'],
                'state' => $validated['shipping_address']['state'] ?? null,
                'postcode' => $validated['shipping_address']['postcode'],
                'country_id' => $validated['shipping_address']['country_id'],
                'delivery_instructions' => $validated['shipping_address']['delivery_instructions'] ?? null,
                'contact_email' => $validated['shipping_address']['contact_email'],
                'contact_phone' => $validated['shipping_address']['contact_phone'],
            ]
        );

        if ($request->boolean('billing_same_as_shipping', true)) {
            $cart->billingAddress()->updateOrCreate(
                ['cart_id' => $cart->id, 'type' => 'billing'],
                [
                    'title' => $validated['shipping_address']['title'] ?? null,
                    'first_name' => $validated['shipping_address']['first_name'],
                    'last_name' => $validated['shipping_address']['last_name'],
                    'company_name' => $validated['shipping_address']['company_name'] ?? null,
                    'line_one' => $validated['shipping_address']['line_one'],
                    'line_two' => $validated['shipping_address']['line_two'] ?? null,
                    'line_three' => $validated['shipping_address']['line_three'] ?? null,
                    'city' => $validated['shipping_address']['city'],
                    'state' => $validated['shipping_address']['state'] ?? null,
                    'postcode' => $validated['shipping_address']['postcode'],
                    'country_id' => $validated['shipping_address']['country_id'],
                    'delivery_instructions' => $validated['shipping_address']['delivery_instructions'] ?? null,
                    'contact_email' => $validated['shipping_address']['contact_email'],
                    'contact_phone' => $validated['shipping_address']['contact_phone'],
                ]
            );
        } else {
            $cart->billingAddress()->updateOrCreate(
                ['cart_id' => $cart->id, 'type' => 'billing'],
                [
                    'title' => $validated['billing_address']['title'] ?? null,
                    'first_name' => $validated['billing_address']['first_name'],
                    'last_name' => $validated['billing_address']['last_name'],
                    'company_name' => $validated['billing_address']['company_name'] ?? null,
                    'line_one' => $validated['billing_address']['line_one'],
                    'line_two' => $validated['billing_address']['line_two'] ?? null,
                    'line_three' => $validated['billing_address']['line_three'] ?? null,
                    'city' => $validated['billing_address']['city'],
                    'state' => $validated['billing_address']['state'] ?? null,
                    'postcode' => $validated['billing_address']['postcode'],
                    'country_id' => $validated['billing_address']['country_id'],
                    'delivery_instructions' => $validated['billing_address']['delivery_instructions'] ?? null,
                    'contact_email' => $validated['billing_address']['contact_email'] ?? null,
                    'contact_phone' => $validated['billing_address']['contact_phone'] ?? null,
                ]
            );
        }

        $cart->calculate();

        return response()->json([
            'status' => 200,
            'message' => 'Addresses saved successfully.',
            'data' => [
                'cart_id' => $cart->id,
            ],
        ]);
    }

    public function initiatePayment(Request $request)
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:senangpay,cash-in-hand'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->first();

        if (! $cart || $cart->lines->isEmpty()) {
            return response()->json([
                'status' => 400,
                'message' => 'Cart is empty.',
                'data' => null,
            ], 400);
        }

        if (! $cart->lines()->where('selected', true)->exists()) {
            return response()->json([
                'status' => 400,
                'message' => 'No items selected for checkout. Please select at least one item.',
                'data' => null,
            ], 400);
        }

        if (! $cart->shippingAddress || ! $cart->billingAddress) {
            return response()->json([
                'status' => 400,
                'message' => 'Please set shipping and billing addresses first.',
                'data' => null,
            ], 400);
        }

        $this->scopeCartToSelected($cart);
        $cart->calculate();

        $existingShippingOption = \Lunar\Facades\ShippingManifest::getShippingOption($cart);

        if (! $existingShippingOption) {
            $shippingRates = Shipping::shippingRates($cart)->get();
            $shippingOptions = Shipping::shippingOptions($cart)->get(
                new ShippingOptionLookup(
                    shippingRates: $shippingRates
                )
            );

            $shippingOptionSet = false;

            foreach ($shippingOptions as $optionResult) {
                if ($optionResult->option->getIdentifier() === 'BASDEL') {
                    $cart->setShippingOption($optionResult->option);
                    $shippingOptionSet = true;
                    break;
                }
            }

            if (! $shippingOptionSet) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Please ensure you have entered a valid shipping address. We are unable to find a shipping option for the provided address.',
                    'data' => null,
                ], 400);
            }
        }

        $cart->shippingBreakdown = null;

        $this->scopeCartToSelected($cart);
        $cart->recalculate();

        if ($validated['payment_method'] === 'senangpay') {
            try {
                $paymentDriver = Payments::driver('senangpay')
                    ->cart($cart)
                    ->withData([])
                    ->setConfig(config('lunar.payments.types.senangpay'));

                $response = $paymentDriver->authorize();

                if ($response && $response->success) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Payment initiated successfully.',
                        'data' => [
                            'payment_url' => $response->data['payment_url'],
                            'reference_number' => $response->data['reference_number'],
                            'order_id' => $response->orderId,
                            'amount' => $cart->total->formatted,
                            'currency' => $cart->currency->code,
                        ],
                    ]);
                }

                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to initiate payment. Please try again later.',
                    'data' => null,
                ], 500);
            } catch (\Exception $e) {
                report($e);

                $message = match (true) {
                    str_contains($e->getMessage(), 'missing shipping option'),
                    str_contains($e->getMessage(), 'shipping') => 'Please ensure you have entered a valid shipping address and try again.',
                    default => 'Something went wrong while processing your payment. Please try again later.',
                };

                return response()->json([
                    'status' => 500,
                    'message' => $message,
                    'data' => null,
                ], 500);
            }
        } else {

            try {
                $order = $cart->createOrder();

                if ($order) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Order created successfully.',
                        'data' => [
                            'order_id' => $order->id,
                            'reference' => $order->reference,
                            'payment_method' => 'cash-in-hand',
                        ],
                    ]);
                }

                return response()->json([
                    'status' => 500,
                    'message' => 'Failed to create order. Please try again later.',
                    'data' => null,
                ], 500);
            } catch (\Exception $e) {
                report($e);

                $message = match (true) {
                    str_contains($e->getMessage(), 'missing shipping option'),
                    str_contains($e->getMessage(), 'shipping') => 'Please ensure you have entered a valid shipping address and try again.',
                    default => 'Something went wrong while creating your order. Please try again later.',
                };

                return response()->json([
                    'status' => 500,
                    'message' => $message,
                    'data' => null,
                ], 500);
            }
        }
    }

    public function checkPaymentStatus(string $reference)
    {

        $order = Order::where(function ($query) use ($reference) {
            $query->whereJsonContains('meta->senangpay_reference', $reference)
                ->orWhereJsonContains('meta->revpay_reference', $reference);
        })->first();

        if (! $order) {
            return response()->json([
                'status' => 404,
                'message' => 'Order not found.',
                'data' => null,
            ], 404);
        }

        $transaction = Transaction::where('reference', $reference)
            ->whereIn('driver', ['senangpay', 'revpay'])
            ->latest()
            ->first();

        $paymentStatus = 'pending';
        if ($transaction) {
            if ($transaction->type === 'capture' && $transaction->success) {
                $paymentStatus = 'success';
            } elseif ($order->status === 'payment-failed') {
                $paymentStatus = 'failed';
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'Payment status retrieved.',
            'data' => [
                'reference_number' => $reference,
                'payment_status' => $paymentStatus,
                'order_id' => $order->id,
                'order_status' => $order->status,
                'transaction_id' => $transaction?->meta['transaction_id'] ?? null,
                'amount' => $order->total->formatted,
                'currency' => $order->currency->code,
            ],
        ]);
    }

    public function orderHistory(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['currency', 'lines'])
            ->latest()
            ->get();

        $orders->each(function ($order) {
            $productLines = $order->lines->where('type', '!=', 'shipping');
            if ($productLines->isNotEmpty()) {
                $productLines->load(['purchasable.product.productType', 'purchasable.product.thumbnail', 'purchasable.values.option']);
            }
        });

        return OrderResource::collection($orders)
            ->additional([
                'status' => 200,
                'message' => 'Orders retrieved successfully.',
            ]);
    }

    public function repay(Request $request, Order $order, SenangpaySignatureService $signatureService)
    {

        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized.',
                'data' => null,
            ], 403);
        }

        if (! in_array($order->status, ['payment-pending', 'payment-failed'])) {
            return response()->json([
                'status' => 400,
                'message' => 'This order is not eligible for repayment.',
                'data' => null,
            ], 400);
        }

        try {
            $merchantId = config('services.senangpay.merchant_id');
            $secretKey = config('services.senangpay.secret_key');
            $baseUrl = config('services.senangpay.base_url', 'https://app.senangpay.my');

            $referenceNumber = 'ORD-'.date('Y').'-'.str_pad($order->id, 5, '0', STR_PAD_LEFT).'-R'.now()->format('His');

            $amount = $signatureService->formatAmount($order->total->value);

            $customerName = '';
            $customerEmail = '';
            $customerPhone = '';

            if ($order->billingAddress) {
                $customerName = $order->billingAddress->first_name.' '.$order->billingAddress->last_name;
                $customerEmail = $order->billingAddress->contact_email;
                $customerPhone = $order->billingAddress->contact_phone;
            }

            $detail = 'Order_'.$referenceNumber;

            $hash = $signatureService->generatePaymentHash(
                $secretKey,
                $detail,
                $amount,
                $referenceNumber
            );

            Transaction::create([
                'order_id' => $order->id,
                'success' => true,
                'type' => 'intent',
                'driver' => 'senangpay',
                'amount' => $order->total,
                'reference' => $referenceNumber,
                'status' => 'pending',
                'card_type' => '',
                'last_four' => '',
                'notes' => 'Repayment intent created',
                'meta' => [
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'amount' => $amount,
                    'detail' => $detail,
                    'is_repayment' => true,
                ],
            ]);

            $paymentUrl = $baseUrl.'/payment/'.$merchantId.'?'.http_build_query([
                'detail' => $detail,
                'amount' => $amount,
                'order_id' => $referenceNumber,
                'hash' => $hash,
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone,
            ]);

            $order->update([
                'status' => 'payment-pending',
                'meta' => array_merge((array) $order->meta, [
                    'senangpay_reference' => $referenceNumber,
                    'repayment_initiated_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::info('SenangPay repayment initiated', [
                'order_id' => $order->id,
                'reference' => $referenceNumber,
                'amount' => $amount,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Repayment initiated successfully.',
                'data' => [
                    'payment_url' => $paymentUrl,
                    'reference_number' => $referenceNumber,
                    'order_id' => $order->id,
                    'amount' => $order->total->formatted,
                    'currency' => $order->currency->code,
                ],
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong while initiating repayment. Please try again later.',
                'data' => null,
            ], 500);
        }
    }

    public function orderDetail(Request $request, Order $order)
    {

        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 403,
                'message' => 'Unauthorized.',
                'data' => null,
            ], 403);
        }

        $order->load([
            'currency',
            'lines',
            'shippingAddress.country',
            'billingAddress.country',
            'transactions',
        ]);

        $productLines = $order->lines->where('type', '!=', 'shipping');
        if ($productLines->isNotEmpty()) {
            $productLines->load(['purchasable.product.productType', 'purchasable.product.thumbnail', 'purchasable.values.option']);
        }

        return OrderResource::make($order)
            ->additional([
                'status' => 200,
                'message' => 'Order retrieved successfully.',
            ]);
    }
}
