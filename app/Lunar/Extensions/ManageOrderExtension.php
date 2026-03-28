<?php

namespace App\Lunar\Extensions;

use Filament\Infolists\Components\Entry;
use Lunar\Admin\Support\Extending\ViewPageExtension;

class ManageOrderExtension extends ViewPageExtension
{
    public function extendOrderSummaryChannelEntry(Entry $entry): Entry
    {
        return $entry->hidden();
    }
}
