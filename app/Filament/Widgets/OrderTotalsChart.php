<?php

namespace App\Filament\Widgets;

use Lunar\Admin\Filament\Widgets\Dashboard\Orders\OrderTotalsChart as BaseOrderTotalsChart;

class OrderTotalsChart extends BaseOrderTotalsChart
{
    protected function getTotalsForPeriod($from, $to)
    {
        $currentPeriod = collect();
        $period = \Carbon\CarbonPeriod::create($from, '1 month', $to);

        $results = $this->getOrderQuery($from, $to)
            ->select(
                \Lunar\Facades\DB::RAW('MAX(currency_code) as currency_code'),
                \Lunar\Facades\DB::RAW('SUM(total) as total'),
                \Lunar\Facades\DB::RAW('SUM(shipping_total) as shipping_total'),
                \Lunar\Facades\DB::RAW('SUM(discount_total) as discount_total'),
                \Lunar\Facades\DB::RAW('SUM(sub_total) as sub_total'),
                \Lunar\Facades\DB::RAW('SUM(tax_total) as tax_total'),
                \Lunar\Facades\DB::RAW(db_date('placed_at', '%M', 'month')),
                \Lunar\Facades\DB::RAW(db_date('placed_at', '%Y', 'year')),
                \Lunar\Facades\DB::RAW(db_date('placed_at', '%Y%m', 'monthstamp'))
            )->groupBy(
                \Lunar\Facades\DB::RAW('month'),
                \Lunar\Facades\DB::RAW('year'),
                \Lunar\Facades\DB::RAW('monthstamp'),
                \Lunar\Facades\DB::RAW(db_date('placed_at', '%Y-%m')),
            )->orderBy(\Lunar\Facades\DB::RAW(db_date('placed_at', '%Y-%m')), 'desc')->get();

        foreach ($period as $date) {
            $report = $results->first(function ($month) use ($date) {
                return $month->monthstamp == $date->format('Ym');
            });
            $currentPeriod->push((object) [
                'order_total' => $report?->total->decimal ?: 0,
                'shipping_total' => $report?->shipping_total->decimal ?: 0,
                'discount_total' => $report?->discount_total->decimal ?: 0,
                'sub_total' => $report?->sub_total->decimal ?: 0,
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'tax_total' => $report?->tax_total->decimal ?: 0,
            ]);
        }

        return $currentPeriod;
    }
}
