<?php

namespace App\Actions\Carts;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;
use Lunar\Actions\AbstractAction;
use Lunar\Exceptions\DisallowMultipleCartOrdersException;
use Lunar\Facades\DB;
use Lunar\Jobs\Orders\MarkAsNewCustomer;
use Lunar\Models\Cart;
use Lunar\Models\Contracts\Cart as CartContract;
use Lunar\Models\Contracts\Order as OrderContract;

class CreateOrder extends AbstractAction
{
    /**
     * Execute the action.
     *
     * Replicates Lunar's default CreateOrder logic but adds post-creation cleanup:
     * - Deletes selected cart lines (they are now part of the order).
     * - Resets remaining (unselected) lines back to selected=true so they are
     *   ready for the customer's next checkout.
     */
    public function execute(
        CartContract $cart,
        bool $allowMultipleOrders = false,
        ?int $orderIdToUpdate = null
    ): self {
        $this->passThrough = DB::transaction(function () use ($cart, $allowMultipleOrders, $orderIdToUpdate) {
            /** @var Cart $cart */
            $order = $cart->draftOrder($orderIdToUpdate)->first() ?: App::make(OrderContract::class);

            if ($cart->hasCompletedOrders() && ! $allowMultipleOrders) {
                throw new DisallowMultipleCartOrdersException;
            }

            $order->fill([
                'cart_id' => $cart->id,
                'fingerprint' => $cart->fingerprint(),
            ]);

            $order = app(Pipeline::class)
                ->send($order)
                ->through(
                    config('lunar.orders.pipelines.creation', [])
                )->thenReturn(function ($order) {
                    return $order;
                });

            $cart->discounts?->each(function ($discount) use ($cart) {
                $discount->markAsUsed($cart)->discount->save();
            });

            $cart->save();

            MarkAsNewCustomer::dispatch($order->id);

            $order->refresh();

            // Remove selected lines from the cart — they are now part of the order.
            $cart->lines()->where('selected', true)->delete();

            // Reset any remaining (unselected) lines to selected=true so they are
            // all ready for the customer's next partial or full checkout.
            $cart->lines()->where('selected', false)->update(['selected' => true]);

            return $order;
        });

        return $this;
    }
}
