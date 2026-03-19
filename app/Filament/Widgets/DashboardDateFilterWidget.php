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
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardDateFilterWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.dashboard-date-filter-widget';

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public array $data = [];

    public int $ordersCount = 0;

    public string $revenue = '0.00';

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

    public function exportCsv(): StreamedResponse
    {
        [$from, $to] = $this->resolveDateRange();

        $orders = Order::whereNotNull('placed_at')
            ->whereBetween('placed_at', [$from, $to])
            ->with(['user', 'billingAddress'])
            ->orderBy('placed_at', 'desc')
            ->get();

        $filename = 'order-stats-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Order ID',
                'Reference',
                'Customer Name',
                'Customer Email',
                'Status',
                'Sub Total (RM)',
                'Currency',
                'Placed At',
            ]);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->id,
                    $order->reference ?? $order->id,
                    $order->user?->name ?? 'Guest',
                    $order->user?->email ?? $order->billingAddress?->contact_email ?? '-',
                    $order->status,
                    number_format($order->sub_total->decimal, 2),
                    $order->currency_code,
                    $order->placed_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
