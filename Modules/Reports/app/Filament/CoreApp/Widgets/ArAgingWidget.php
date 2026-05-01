<?php

declare(strict_types=1);

namespace Modules\Reports\Filament\CoreApp\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Enums\InvoiceStatusEnum;

final class ArAgingWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 6;

    // protected int|string|array $columnSpan = ['md' => 1, 'xl' => 1];

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected ?string $maxHeight = '240px';

    public function getHeading(): ?string
    {
        return __('AR Aging (Accounts Receivable)');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('A report of all issued sales invoices grouped by customer, showing the aging of pending amounts.');
    }

    public function getColumnSpan(): int|string|array
    {
        return 1;
    }

    protected function getData(): array
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return ['datasets' => [], 'labels' => []];
        }

        $companyId = $tenant->id;
        $cacheKey = "rpt_ar_aging_chart_{$companyId}";

        return Cache::tags(['reports', "company:{$companyId}"])
            ->remember($cacheKey, 900, function () use ($companyId) {
                $row = DB::table('sales_invoices')
                    ->where('company_id', $companyId)
                    ->where('status', InvoiceStatusEnum::Issued->value)
                    ->whereNull('deleted_at')
                    ->selectRaw('
                        SUM(CASE WHEN (NOW()::date - COALESCE(due_date, issue_date)) <= 0 THEN total - paid_amount - credited_amount ELSE 0 END) as current_band,
                        SUM(CASE WHEN (NOW()::date - COALESCE(due_date, issue_date)) BETWEEN 1 AND 30 THEN total - paid_amount - credited_amount ELSE 0 END) as band_30,
                        SUM(CASE WHEN (NOW()::date - COALESCE(due_date, issue_date)) BETWEEN 31 AND 60 THEN total - paid_amount - credited_amount ELSE 0 END) as band_60,
                        SUM(CASE WHEN (NOW()::date - COALESCE(due_date, issue_date)) BETWEEN 61 AND 90 THEN total - paid_amount - credited_amount ELSE 0 END) as band_90,
                        SUM(CASE WHEN (NOW()::date - COALESCE(due_date, issue_date)) > 90 THEN total - paid_amount - credited_amount ELSE 0 END) as band_90plus
                    ')
                    ->first();

                $data = [
                    round((float) ($row->current_band ?? 0), 2),
                    round((float) ($row->band_30 ?? 0), 2),
                    round((float) ($row->band_60 ?? 0), 2),
                    round((float) ($row->band_90 ?? 0), 2),
                    round((float) ($row->band_90plus ?? 0), 2),
                ];

                if (array_sum($data) === 0.0) {
                    return ['datasets' => [], 'labels' => []];
                }

                return [
                    'datasets' => [
                        [
                            'label' => __('Pending ($)'),
                            'data' => $data,
                            'backgroundColor' => [
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(234, 179, 8, 0.8)',
                                'rgba(249, 115, 22, 0.8)',
                                'rgba(239, 68, 68, 0.8)',
                                'rgba(127, 29, 29, 0.8)',
                            ],
                        ],
                    ],
                    'labels' => [
                        __('Current'),
                        '1-30 '.__('days'),
                        '31-60 '.__('days'),
                        '61-90 '.__('days'),
                        '>90 '.__('days'),
                    ],
                ];
            });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
            ],
        ];
    }
}
