<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardDateFilterWidget;
use App\Filament\Widgets\OrderTotalsChart;
use Lunar\Admin\Filament\Pages\Dashboard as LunarBaseDashboard;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\AverageOrderValueChart;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\LatestOrdersTable;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\NewVsReturningCustomersChart;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\OrdersSalesChart;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\OrderStatsOverview;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\PopularProductsTable;

class LunarDashboard extends LunarBaseDashboard
{
    public function getWidgets(): array
    {
        return [
            DashboardDateFilterWidget::class,
            OrderStatsOverview::class,
            OrderTotalsChart::class,
            OrdersSalesChart::class,
            AverageOrderValueChart::class,
            NewVsReturningCustomersChart::class,
            PopularProductsTable::class,
            LatestOrdersTable::class,
        ];
    }
}
