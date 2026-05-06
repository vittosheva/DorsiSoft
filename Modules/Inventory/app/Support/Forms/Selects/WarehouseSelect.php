<?php

declare(strict_types=1);

namespace Modules\Inventory\Support\Forms\Selects;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Livewire\Component;
use Modules\Inventory\Models\Warehouse;

final class WarehouseSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Available :resources', ['resources' => __('Warehouses')]))
            ->options(
                fn (): array => Warehouse::query()
                    ->where('company_id', Filament::getTenant()?->getKey())
                    ->where('is_active', true)
                    ->pluck('name', 'id')
                    ->all()
            )
            ->searchable()
            ->nullable()
            ->live()
            ->afterStateUpdated(fn ($state, Component $livewire) => $livewire->dispatch('warehouse-selected', warehouseId: $state ? (int) $state : null))
            ->extraAttributes(['class' => 'z-50']);
    }

    public static function getDefaultName(): ?string
    {
        return 'warehouse_id';
    }
}
