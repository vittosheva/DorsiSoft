<?php

declare(strict_types=1);

namespace Modules\Reports\Filament\CoreApp\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Enums\InvoiceStatusEnum;

final class TopCustomersWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    // protected int|string|array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected ?string $maxHeight = '190px';

    public function getHeading(): ?string
    {
        return __('Top 8 Customers');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('Based on sales invoices issued this year.');
    }

    public function getColumnSpan(): int|string|array
    {
        return 2;
    }

    protected function getData(): array
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return ['datasets' => [], 'labels' => []];
        }

        $companyId = $tenant->id;
        $startDate = $this->pageFilters['start_date'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->pageFilters['end_date'] ?? now()->toDateString();

        $cacheKey = "rpt_top_customers_{$companyId}_{$startDate}_{$endDate}";

        return Cache::tags(['reports', "company:{$companyId}"])
            ->remember($cacheKey, 900, function () use ($companyId, $startDate, $endDate) {
                $rows = DB::table('sales_invoices')
                    ->where('company_id', $companyId)
                    ->whereIn('status', [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value])
                    ->whereNull('deleted_at')
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->selectRaw('customer_name, SUM(total) as revenue')
                    ->groupBy('customer_name')
                    ->orderByDesc('revenue')
                    ->limit(8)
                    ->get();

                $colors = [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(99, 102, 241, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(234, 179, 8, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(20, 184, 166, 0.8)',
                ];

                return [
                    'datasets' => [
                        [
                            'label' => __('Revenue'),
                            'data' => $rows->pluck('revenue')->map(fn ($v) => (float) $v)->values()->all(),
                            'backgroundColor' => $colors,
                            'borderColor' => array_map(fn ($c) => str_replace('0.8', '1', $c), $colors),
                            'borderWidth' => 1,
                        ],
                    ],
                    'labels' => $rows->map(fn ($r) => mb_strimwidth($r->customer_name, 0, 25, '...'))->values()->all(),
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
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return '$' + value.toLocaleString(); }",
                    ],
                ],
            ],
        ];
    }
}
