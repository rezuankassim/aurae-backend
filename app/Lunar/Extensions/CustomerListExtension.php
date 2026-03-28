<?php

namespace App\Lunar\Extensions;

use Lunar\Admin\Support\Extending\ListPageExtension;

class CustomerListExtension extends ListPageExtension
{
    public function headerActions(array $actions): array
    {
        return [];
    }
}
