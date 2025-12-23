<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserDevice extends Model
{
    protected $guarded = [];

    public function deviceable()
    {
        return $this->morphTo();
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DeviceLocation::class);
    }

    public function latestLocation()
    {
        return $this->hasOne(DeviceLocation::class)->latestOfMany();
    }
}
