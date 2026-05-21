<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Lunar\Models\OrderLine as LunarOrderLine;

class OrderLine extends LunarOrderLine
{
    /**
     * Return the polymorphic relation.
     *
     * Include withTrashed so that soft-deleted purchasables
     * (e.g. ProductVariant) are still loaded for historical orders.
     */
    public function purchasable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
