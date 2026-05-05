<?php

declare(strict_types=1);

namespace Modules\Finance\Support\Forms\Selects;

use Filament\Forms\Components\Select;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\PriceListResource;
use Modules\Finance\Models\PriceList;

final class PriceListSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->live()
            ->options($this->buildPriceListOptions())
            ->afterStateUpdated(function (?string $state, $livewire): void {
                $livewire->dispatch('price-list-selected', priceListId: $state ? (int) $state : null);
            })
            ->prefixIcon(PriceListResource::getNavigationIcon())
            ->nullable()
            ->searchable()
            ->preload();
    }

    public static function getDefaultName(): ?string
    {
        return 'price_list_id';
    }

    private function buildPriceListOptions(): array
    {
        return PriceList::query()
            ->active()
            ->get(['id', 'code', 'name', 'currency_code', 'start_date', 'end_date', 'is_default', 'description'])
            ->mapWithKeys(function (PriceList $priceList): array {
                $defaultBadge = $priceList->is_default ? ' ★' : '';
                $label = "[{$priceList->code}] {$priceList->name}{$defaultBadge} — {$priceList->currency_code}";

                $details = [];

                if ($priceList->start_date && $priceList->end_date) {
                    $details[] = "{$priceList->start_date->format('d/m/Y')} – {$priceList->end_date->format('d/m/Y')}";
                } elseif ($priceList->start_date) {
                    $details[] = "Desde {$priceList->start_date->format('d/m/Y')}";
                } elseif ($priceList->end_date) {
                    $details[] = "Hasta {$priceList->end_date->format('d/m/Y')}";
                }

                if ($priceList->description) {
                    $details[] = $priceList->description;
                }

                if (! empty($details)) {
                    $label .= ' · '.implode(' · ', $details);
                }

                return [$priceList->id => $label];
            })
            ->all();
    }
}
