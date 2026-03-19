<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Widgets\Widget;
use Lunar\Models\Order;

class DashboardDateFilterWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.dashboard-date-filter-widget';

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public array $data = [];

    public int $ordersCount = 0;

    public string $revenue = '0.00';

    public int $activeCustomers = 0;

    public function mount(): void
    {
        $this->form->fill([
            'range'    => '30d',
            'dateFrom' => null,
            'dateTo'   => null,
        ]);

        $this->refreshStats();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(['default' => 1, 'sm' => 2, 'lg' => 4])
                    ->schema([
                        Select::make('range')
                            ->label('Date Range')
                            ->options([
                                '7d'     => 'Last 7 days',
                                '30d'    => 'Last 30 days',
                                '90d'    => 'Last 3 months',
                                'custom' => 'Custom date range',
                            ])
                            ->default('30d')
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshStats()),

                        DatePicker::make('dateFrom')
                            ->label('From')
                            ->displayFormat('d M Y')
                            ->maxDate(now())
                            ->visible(fn (Get $get) => $get('range') === 'custom')
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshStats()),

                        DatePicker::make('dateTo')
                            ->label('To')
                            ->displayFormat('d M Y')
                            ->maxDate(now())
                            ->visible(fn (Get $get) => $get('range') === 'custom')
                            ->live()
                            ->afterStateUpdated(fn () => $this->refreshStats()),
                    ]),
            ])
            ->statePath('data');
    }

    public function refreshStats(): void
    {
        [$from, $to] = $this->resolveDateRange();

        $base = Order::whereNotNull('placed_at')
            ->whereBetween('placed_at', [$from, $to]);

        $this->ordersCount = (clone $base)->count();

        $rawRevenue = (clone $base)->sum('sub_total');
        $this->revenue = number_format($rawRevenue / 100, 2);

        $this->activeCustomers = (clone $base)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
    }

    private function resolveDateRange(): array
    {
        $range    = $this->data['range'] ?? '30d';
        $dateFrom = $this->data['dateFrom'] ?? null;
        $dateTo   = $this->data['dateTo'] ?? null;

        if ($range === 'custom' && $dateFrom && $dateTo) {
            return [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ];
        }

        $days = match ($range) {
            '7d'    => 7,
            '90d'   => 90,
            default => 30,
        };

        return [
            Carbon::now()->subDays($days)->startOfDay(),
            Carbon::now()->endOfDay(),
        ];
    }

    public function getLabel(): string
    {
        $range    = $this->data['range'] ?? '30d';
        $dateFrom = $this->data['dateFrom'] ?? null;
        $dateTo   = $this->data['dateTo'] ?? null;

        if ($range === 'custom' && $dateFrom && $dateTo) {
            return Carbon::parse($dateFrom)->format('d M Y').' – '.Carbon::parse($dateTo)->format('d M Y');
        }

        return match ($range) {
            '7d'    => 'Last 7 days',
            '90d'   => 'Last 3 months',
            default => 'Last 30 days',
        };
    }
}
