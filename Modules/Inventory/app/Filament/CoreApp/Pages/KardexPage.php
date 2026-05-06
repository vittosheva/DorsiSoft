<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\KardexService;
use Modules\Inventory\Support\Forms\Selects\ProductSelect;
use UnitEnum;

final class KardexPage extends Page implements HasForms
{
    use InteractsWithForms;

    public array $data = [];

    public ?int $productId = null;

    public ?int $warehouseId = null;

    public ?string $fromDate = null;

    public ?string $toDate = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 40;

    protected string $view = 'inventory::filament.pages.kardex-page';

    public static function getNavigationLabel(): string
    {
        return __('Kardex');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('Inventory');
    }

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();

        $this->filtersForm->fill([
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
        ]);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make(12)
                    ->schema([
                        ProductSelect::make('productId')
                            ->columnSpan(3),

                        Select::make('warehouseId')
                            ->options(fn () => Warehouse::query()
                                ->where('company_id', Filament::getTenant()?->getKey())
                                ->active()
                                ->pluck('name', 'id'))
                            ->columnSpan(3),

                        DatePicker::make('fromDate')
                            ->columnSpan(2),

                        DatePicker::make('toDate')
                            ->columnSpan(2),
                    ]),
            ])
            ->columns(1);
    }

    public function applyFilters(): void
    {
        $data = $this->filtersForm->getState();

        $this->productId = ($data['productId'] ?? null) ? (int) $data['productId'] : null;
        $this->warehouseId = ($data['warehouseId'] ?? null) ? (int) $data['warehouseId'] : null;
        $this->fromDate = $data['fromDate'] ?? null;
        $this->toDate = $data['toDate'] ?? null;
    }

    public function getKardexEntries(): Collection
    {
        if ($this->productId === null || $this->warehouseId === null) {
            return collect();
        }

        return app(KardexService::class)->kardex(
            productId: $this->productId,
            warehouseId: $this->warehouseId,
            from: $this->fromDate ? Carbon::parse($this->fromDate) : null,
            to: $this->toDate ? Carbon::parse($this->toDate) : null,
        );
    }

    public function getTitle(): string|Htmlable
    {
        return __('Kardex — Inventory Ledger');
    }

    protected function getForms(): array
    {
        return ['filtersForm'];
    }
}
