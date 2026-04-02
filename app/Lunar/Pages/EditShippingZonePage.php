<?php

namespace App\Lunar\Pages;

use Lunar\Shipping\Filament\Resources\ShippingZoneResource\Pages\EditShippingZone;

class EditShippingZonePage extends EditShippingZone
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
