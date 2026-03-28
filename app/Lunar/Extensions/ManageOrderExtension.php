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
        return array_map(function ($action) {
            if ($action instanceof BaseUpdateStatusAction) {
                $caller = $this->caller;

                return UpdateStatusAction::make('update_status')
                    ->after(function () use ($caller) {
                        $caller->dispatch(ActivityLogFeed::UPDATED)->to(ActivityLogFeed::class);
                    });
            }

            return $action;
        }, $actions);
    }
}
