<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Lunar\Models\OrderLine as LunarOrderLine;

class OrderLine extends LunarOrderLine
{
    public function purchasable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
