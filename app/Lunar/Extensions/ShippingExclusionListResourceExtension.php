<?php

namespace App\Lunar\Extensions;

use App\Lunar\RelationManagers\ShippingExclusionRelationManager;
use Lunar\Admin\Support\Extending\ResourceExtension;
use Lunar\Shipping\Filament\Resources\ShippingExclusionListResource\RelationManagers\ShippingExclusionRelationManager as BaseShippingExclusionRelationManager;

class ShippingExclusionListResourceExtension extends ResourceExtension
{
    public function getRelations(array $managers): array
    {
        return array_map(
            fn ($manager) => $manager === BaseShippingExclusionRelationManager::class
                ? ShippingExclusionRelationManager::class
                : $manager,
            $managers
        );
    }
}
