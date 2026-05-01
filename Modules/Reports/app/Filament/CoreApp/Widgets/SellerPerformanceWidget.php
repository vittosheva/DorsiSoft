<?php

declare(strict_types=1);

namespace Modules\Reports\Filament\CoreApp\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Enums\InvoiceStatusEnum;

final class SellerPerformanceWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    // protected int|string|array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected ?string $maxHeight = '230px';

    public function getHeading(): ?string
    {
        return __('Revenue by Seller (YTD)');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('A report of all issued sales invoices grouped by seller, showing key performance indicators such as total revenue, collected amount, pending amount, average ticket, and collection rate.');
    }

    public function getColumnSpan(): int|string|array
    {
        return 2;
    }

    protected function getData(): array
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return [
                'datasets' => [[
                    'label' => __('No data'),
                    'data' => [0],
                    'backgroundColor' => 'rgba(229,231,235,0.8)',
                ]],
                'labels' => [__('No sellers')],
            ];
        }

        $companyId = $tenant->id;
        $year = now()->year;

        $cacheKey = "rpt_seller_perf_{$companyId}_{$year}";

        return Cache::tags(['reports', "company:{$companyId}"])
            ->remember($cacheKey, 900, function () use ($companyId, $year) {
                $sellers = DB::table('sales_invoices')
                    ->where('company_id', $companyId)
                    ->whereIn('status', [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value])
                    ->whereNull('deleted_at')
                    ->whereYear('issue_date', $year)
                    ->whereNotNull('seller_name')
                    ->selectRaw('seller_name, SUM(total) as revenue, COUNT(*) as invoices, SUM(paid_amount) as collected')
                    ->groupBy('seller_name')
                    ->orderByDesc('revenue')
                    ->limit(10)
                    ->get();

                if ($sellers->isEmpty()) {
                    return [
                        'datasets' => [[
                            'label' => __('No data'),
                            'data' => [0],
                            'backgroundColor' => 'rgba(229,231,235,0.8)',
                        ]],
                        'labels' => [__('No sellers')],
                    ];
                }

                return [
                    'datasets' => [
                        [
                            'label' => __('Invoiced'),
                            'data' => $sellers->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->values()->all(),
                            'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                        ],
                        [
                            'label' => __('Collected'),
                            'data' => $sellers->pluck('collected')->map(fn ($v) => round((float) $v, 2))->values()->all(),
                            'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                        ],
                    ],
                    'labels' => $sellers->map(fn ($r) => mb_strimwidth($r->seller_name, 0, 25, '...'))->values()->all(),
                ];
            });
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'stacked' => false,
                    'ticks' => [
                        'callback' => "function(value) { return '$' + value.toLocaleString(); }",
                    ],
                ],
            ],
        ];
    }
}
