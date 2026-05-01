<?php

declare(strict_types=1);

namespace Modules\Reports\Filament\CoreApp\Widgets;

use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Enums\QuotationStatusEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\Quotation;

final class SalesKpiWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = -999;

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $tenant = filament()->getTenant();

        if (! $tenant) {
            return [];
        }

        $companyId = $tenant->id;
        $startDate = $this->pageFilters['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $this->pageFilters['end_date'] ?? now()->toDateString();

        $issuedStatuses = [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value];

        $granularity = $this->determineGranularity($startDate, $endDate);
        $periods = $this->buildPeriods($startDate, $endDate, $granularity);

        $cacheKey = "rpt_sales_kpi_{$companyId}_{$startDate}_{$endDate}_{$granularity}";

        $stats = Cache::tags(['reports', "company:{$companyId}"])
            ->remember($cacheKey, 300, function () use ($companyId, $startDate, $endDate, $issuedStatuses, $granularity, $periods) {
                $periodSql = $this->getPeriodSql($granularity);

                $current = Invoice::query()
                    ->selectRaw('COUNT(*) as count, SUM(total) as revenue, SUM(paid_amount) as collected')
                    ->where('company_id', $companyId)
                    ->whereIn('status', $issuedStatuses)
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->first();

                $previousStart = now()->parse($startDate)->subMonth()->startOfMonth()->toDateString();
                $previousEnd = now()->parse($startDate)->subMonth()->endOfMonth()->toDateString();

                $previous = (float) (Invoice::query()
                    ->selectRaw('SUM(total) as revenue')
                    ->where('company_id', $companyId)
                    ->whereIn('status', $issuedStatuses)
                    ->whereBetween('issue_date', [$previousStart, $previousEnd])
                    ->value('revenue') ?? 0);

                $arTotal = (float) (Invoice::query()
                    ->selectRaw('SUM(total - paid_amount - credited_amount) as pending')
                    ->where('company_id', $companyId)
                    ->where('status', InvoiceStatusEnum::Issued->value)
                    ->value('pending') ?? 0);

                $quotationConversion = Quotation::query()
                    ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as accepted', [QuotationStatusEnum::Accepted->value])
                    ->where('company_id', $companyId)
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->whereNotIn('status', [QuotationStatusEnum::Draft->value])
                    ->first();

                // Series: revenue
                $revenueRows = Invoice::query()
                    ->selectRaw("{$periodSql} as period, COALESCE(SUM(total), 0) as revenue")
                    ->where('company_id', $companyId)
                    ->whereIn('status', $issuedStatuses)
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->groupBy('period')
                    ->orderBy('period')
                    ->pluck('revenue', 'period')
                    ->toArray();

                // Series: invoiced and collected per period for collection rate
                $collectRows = Invoice::query()
                    ->selectRaw("{$periodSql} as period, COALESCE(SUM(total), 0) as invoiced, COALESCE(SUM(paid_amount), 0) as collected")
                    ->where('company_id', $companyId)
                    ->whereIn('status', $issuedStatuses)
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get()
                    ->keyBy('period');

                // Series: accounts receivable per period (pending per invoice issuance)
                $arRows = Invoice::query()
                    ->selectRaw("{$periodSql} as period, COALESCE(SUM(total - paid_amount - credited_amount), 0) as pending")
                    ->where('company_id', $companyId)
                    ->where('status', InvoiceStatusEnum::Issued->value)
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->groupBy('period')
                    ->orderBy('period')
                    ->pluck('pending', 'period')
                    ->toArray();

                // Series: invoices issued count
                $countRows = Invoice::query()
                    ->selectRaw("{$periodSql} as period, COUNT(*) as count")
                    ->where('company_id', $companyId)
                    ->whereIn('status', $issuedStatuses)
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->groupBy('period')
                    ->orderBy('period')
                    ->pluck('count', 'period')
                    ->toArray();

                // Series: quotation conversion (optional)
                $quoteRows = Quotation::query()
                    ->selectRaw("{$periodSql} as period, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as accepted", [QuotationStatusEnum::Accepted->value])
                    ->where('company_id', $companyId)
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->whereNotIn('status', [QuotationStatusEnum::Draft->value])
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get()
                    ->keyBy('period');

                // Build filled series arrays (numeric indexed)
                $seriesRevenue = $this->fillSeries($periods, $revenueRows);
                $seriesAr = $this->fillSeries($periods, $arRows);
                $seriesInvoices = $this->fillSeries($periods, $countRows);

                $seriesCollectionRate = array_map(function ($period) use ($collectRows) {
                    $row = $collectRows->get($period);
                    if (! $row) {
                        return 0;
                    }
                    $invoiced = (float) $row->invoiced;
                    $collected = (float) $row->collected;

                    return $invoiced > 0 ? round($collected / $invoiced * 100, 1) : 0;
                }, $periods);

                $seriesConversion = array_map(function ($period) use ($quoteRows) {
                    $row = $quoteRows->get($period);
                    if (! $row) {
                        return 0;
                    }
                    $total = (int) $row->total;
                    $accepted = (int) $row->accepted;

                    return $total > 0 ? round($accepted / $total * 100, 1) : 0;
                }, $periods);

                $revenue = (float) ($current->revenue ?? 0);
                $collected = (float) ($current->collected ?? 0);
                $count = (int) ($current->count ?? 0);
                $collectionRate = $revenue > 0 ? round($collected / $revenue * 100, 1) : 0;
                $revenueDelta = $previous > 0 ? round(($revenue - $previous) / $previous * 100, 1) : 0;
                $conversionRate = ($quotationConversion->total ?? 0) > 0
                    ? round($quotationConversion->accepted / $quotationConversion->total * 100, 1)
                    : 0;

                return compact('revenue', 'collected', 'count', 'collectionRate', 'revenueDelta', 'arTotal', 'conversionRate') + ['series' => [
                    'revenue' => $seriesRevenue,
                    'ar' => $seriesAr,
                    'invoices' => $seriesInvoices,
                    'collectionRate' => $seriesCollectionRate,
                    'conversion' => $seriesConversion,
                ]];
            });

        return [
            Stat::make(__('Invoices Issued'), number_format((int) $stats['count']))
                ->description($stats['conversionRate'].'% '.__('quote conversion'))
                ->descriptionIcon(Heroicon::DocumentText)
                ->chart($stats['series']['invoices'] ?? [])
                ->color('primary'),

            Stat::make(__('Accounts Receivable'), '$'.number_format((float) $stats['arTotal'], 2))
                ->description(__('Outstanding invoices'))
                ->descriptionIcon(Heroicon::ExclamationCircle)
                ->chart($stats['series']['ar'] ?? [])
                ->color($stats['arTotal'] > 0 ? 'warning' : 'success'),

            Stat::make(__('Collection Rate'), $stats['collectionRate'].'%')
                ->description(__('Paid of invoiced'))
                ->descriptionIcon(Heroicon::CreditCard)
                ->chart($stats['series']['collectionRate'] ?? [])
                ->color($stats['collectionRate'] >= 80 ? 'success' : ($stats['collectionRate'] >= 50 ? 'warning' : 'danger')),

            Stat::make(__('Revenue'), '$'.number_format((float) $stats['revenue'], 2))
                ->description($stats['revenueDelta'] >= 0
                    ? '+'.abs($stats['revenueDelta']).'% '.str(__('vs last month'))
                    : '-'.abs($stats['revenueDelta']).'% '.str(__('vs last month')))
                ->descriptionIcon($stats['revenueDelta'] >= 0
                    ? Heroicon::ArrowTrendingUp
                    : Heroicon::ArrowTrendingDown)
                ->color($stats['revenueDelta'] >= 0 ? 'success' : 'warning'),
        ];
    }

    private function determineGranularity(string $startDate, string $endDate): string
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end) + 1;

        if ($days <= 14) {
            return 'day';
        }

        if ($days <= 90) {
            return 'week';
        }

        return 'month';
    }

    private function getPeriodSql(string $granularity): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return match ($granularity) {
                'day' => '(issue_date::date)',
                'week' => "to_char(date_trunc('week', issue_date)::date, 'YYYY-MM-DD')",
                'month' => "to_char(issue_date, 'YYYY-MM')",
                default => '(issue_date::date)',
            };
        }

        // Default to MySQL-compatible expressions
        return match ($granularity) {
            'day' => 'DATE(issue_date)',
            'week' => "DATE_FORMAT(DATE_SUB(issue_date, INTERVAL WEEKDAY(issue_date) DAY), '%Y-%m-%d')",
            'month' => "DATE_FORMAT(issue_date, '%Y-%m')",
            default => 'DATE(issue_date)',
        };
    }

    private function buildPeriods(string $startDate, string $endDate, string $granularity): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $periods = [];

        if ($granularity === 'day') {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $periods[] = $date->toDateString();
            }

            return $periods;
        }

        if ($granularity === 'week') {
            $cursor = $start->copy()->startOfWeek();
            $endCursor = $end->copy()->endOfWeek();
            while ($cursor->lte($endCursor)) {
                $periods[] = $cursor->toDateString();
                $cursor->addWeek();
            }

            return $periods;
        }

        $cursor = $start->copy()->startOfMonth();
        $endCursor = $end->copy()->endOfMonth();
        while ($cursor->lte($endCursor)) {
            $periods[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $periods;
    }

    private function fillSeries(array $periods, array $assocRows): array
    {
        $result = [];
        foreach ($periods as $p) {
            $result[] = isset($assocRows[$p]) ? (float) $assocRows[$p] : 0.0;
        }

        return $result;
    }
}
