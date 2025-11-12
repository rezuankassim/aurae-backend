<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDevice extends Model
{
    protected $guarded = [];

    public function deviceable()
    {
        return $this->morphTo();
    }
}
