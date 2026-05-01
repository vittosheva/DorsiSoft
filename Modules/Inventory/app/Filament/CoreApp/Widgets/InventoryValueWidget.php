<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Modules\Inventory\Models\InventoryBalance;
use Modules\Inventory\Models\Warehouse;

final class InventoryValueWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $companyId = Filament::getTenant()?->getKey();

        $cacheKey = "inventory.value.{$companyId}";

        $data = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($companyId): array {
            $totalValue = InventoryBalance::where('company_id', $companyId)
                ->selectRaw('SUM(quantity_available * average_cost) as total_value')
                ->value('total_value') ?? 0;

            $totalItems = InventoryBalance::where('company_id', $companyId)
                ->where('quantity_available', '>', 0)
                ->count();

            $belowReorder = InventoryBalance::where('inv_balances.company_id', $companyId)
                ->belowReorderPoint()
                ->count();

            $warehouseCount = Warehouse::where('company_id', $companyId)
                ->where('is_active', true)
                ->count();

            return compact('totalValue', 'totalItems', 'belowReorder', 'warehouseCount');
        });

        return [
            Stat::make(__('Total Inventory Value'), number_format((float) $data['totalValue'], 2))
                ->description(__('Sum of available stock × average cost'))
                ->color('success'),

            Stat::make(__('Active SKUs'), number_format($data['totalItems']))
                ->description(__('Products with stock > 0'))
                ->color('primary'),

            Stat::make(__('Below Reorder Point'), number_format($data['belowReorder']))
                ->description(__('Products needing replenishment'))
                ->color($data['belowReorder'] > 0 ? 'danger' : 'success'),

            Stat::make(__('Active Warehouses'), number_format($data['warehouseCount']))
                ->color('info'),
        ];
    }
}
