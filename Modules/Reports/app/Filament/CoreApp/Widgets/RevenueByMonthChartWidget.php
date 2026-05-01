<?php

declare(strict_types=1);

namespace Modules\Reports\Filament\CoreApp\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Enums\InvoiceStatusEnum;

final class RevenueByMonthChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    // protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected ?string $maxHeight = '280px';

    public function getHeading(): ?string
    {
        return __('Monthly Revenue (Last 12 months)');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('A report of all sales invoices issued by the company, showing the total invoiced amount and collected amount for each of the last 12 months. This chart helps visualize revenue trends and cash flow over the past year.');
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
        $cacheKey = "rpt_revenue_chart_{$companyId}";

        return Cache::tags(['reports', "company:{$companyId}"])
            ->remember($cacheKey, 900, function () use ($companyId) {
                $rows = DB::table('sales_invoices')
                    ->where('company_id', $companyId)
                    ->whereIn('status', [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value])
                    ->whereNull('deleted_at')
                    ->where('issue_date', '>=', now()->subMonths(11)->startOfMonth())
                    ->selectRaw("TO_CHAR(issue_date, 'YYYY-MM') as month, SUM(total) as revenue, SUM(paid_amount) as collected")
                    ->groupBy(DB::raw("TO_CHAR(issue_date, 'YYYY-MM')"))
                    ->orderBy('month')
                    ->get()
                    ->keyBy('month');

                $labels = [];
                $revenueData = [];
                $collectedData = [];

                for ($i = 11; $i >= 0; $i--) {
                    $month = now()->subMonths($i)->format('Y-m');
                    $labels[] = now()->subMonths($i)->translatedFormat('M Y');
                    $revenueData[] = (float) ($rows[$month]->revenue ?? 0);
                    $collectedData[] = (float) ($rows[$month]->collected ?? 0);
                }

                return [
                    'datasets' => [
                        [
                            'label' => __('Invoiced'),
                            'data' => $revenueData,
                            'borderColor' => 'rgb(59, 130, 246)',
                            'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                            'fill' => true,
                            'tension' => 0.3,
                        ],
                        [
                            'label' => __('Collected'),
                            'data' => $collectedData,
                            'borderColor' => 'rgb(34, 197, 94)',
                            'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                            'fill' => true,
                            'tension' => 0.3,
                        ],
                    ],
                    'labels' => $labels,
                ];
            });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return '$' + value.toLocaleString(); }",
                    ],
                ],
            ],
        ];
    }
}
