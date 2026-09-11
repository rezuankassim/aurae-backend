<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class GeneralSettingResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
