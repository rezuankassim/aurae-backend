<?php

namespace App\Lunar\Extensions;

use App\Lunar\Actions\UpdateStatusAction;
use Filament\Infolists\Components\Entry;
use Lunar\Admin\Livewire\Components\ActivityLogFeed;
use Lunar\Admin\Support\Actions\Orders\UpdateStatusAction as BaseUpdateStatusAction;
use Lunar\Admin\Support\Extending\ViewPageExtension;

class ManageOrderExtension extends ViewPageExtension
{
    public function extendOrderSummaryChannelEntry(Entry $entry): Entry
    {
        return $entry->hidden();
    }

    public function headerActions(array $actions): array
    {
        $order = $this->caller->record;

        return array_map(function ($action) use ($order) {
            if ($action instanceof BaseUpdateStatusAction) {
                $caller = $this->caller;

                return UpdateStatusAction::make('update_status')
                    ->after(function () use ($caller) {
                        $caller->dispatch(ActivityLogFeed::UPDATED)->to(ActivityLogFeed::class);
                    });
            }

        // Always hide "Refund" button
            if ($action->getName() === 'refund') {
                return $action->visible(false);
            }

            // Hide "Capture Payment" for orders with payment-pending or payment-failed status
            if ($action->getName() === 'capture' && in_array($order->status, ['payment-pending', 'payment-failed'])) {
                return $action->visible(false);
            }

            return $action;
        }, $actions);
    }
}
