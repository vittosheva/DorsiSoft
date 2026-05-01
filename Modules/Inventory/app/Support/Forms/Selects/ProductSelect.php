<?php

declare(strict_types=1);

namespace Modules\Inventory\Support\Forms\Selects;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\Product;

final class ProductSelect extends Select
{
    protected bool $onlyActive = true;

    protected bool $onlyForSale = false;

    protected bool $onlyForPurchase = false;

    protected bool $onlyTracksLots = false;

    protected bool $onlyTracksSerials = false;

    protected bool $showDetails = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->searchable()
            ->getSearchResultsUsing(function (?string $search): array {
                $columns = $this->showDetails
                    ? ['id', 'code', 'name', 'sale_price', 'unit_id']
                    : ['id', 'code', 'name'];

                $query = Product::query()
                    ->select($columns)
                    ->when($this->showDetails, fn (Builder $q) => $q->with(['unit:id,code']))
                    ->when($this->onlyActive, fn (Builder $q) => $q->active())
                    ->when($this->onlyForSale, fn (Builder $q) => $q->forSale())
                    ->when($this->onlyForPurchase, fn (Builder $q) => $q->forPurchase())
                    ->when($this->onlyTracksLots, fn (Builder $q) => $q->where('tracks_lots', true))
                    ->when($this->onlyTracksSerials, fn (Builder $q) => $q->where('tracks_serials', true))
                    ->when(filled($search), function (Builder $q) use ($search): void {
                        $q->where(function (Builder $inner) use ($search): void {
                            $inner->where('code', 'like', $search.'%')
                                ->orWhere('name', 'like', $search.'%');

                            if (mb_strlen($search) >= 3) {
                                $inner->orWhereFullText('name', $search);
                            }
                        });
                    })
                    ->orderBy('name')
                    ->limit(config('dorsi.filament.select_filter_options_limit', 50));

                return $query
                    ->get()
                    ->mapWithKeys(fn (Product $product): array => [
                        $product->id => $this->buildLabel($product),
                    ])
                    ->all();
            })
            ->getOptionLabelUsing(function (mixed $value): ?string {
                $columns = $this->showDetails
                    ? ['id', 'code', 'name', 'sale_price', 'unit_id']
                    : ['id', 'code', 'name'];

                $product = Product::query()
                    ->select($columns)
                    ->when($this->showDetails, fn (Builder $q) => $q->with(['unit:id,code']))
                    ->find($value);

                return $product ? $this->buildLabel($product) : null;
            })
            ->getOptionLabelFromRecordUsing(fn (Product $product): string => $this->buildLabel($product));
    }

    public static function getDefaultName(): ?string
    {
        return 'product_id';
    }

    /** Add price and unit to the option label. */
    public function withDetails(): static
    {
        $this->showDetails = true;

        return $this;
    }

    /** Restrict to products available for sale (is_for_sale = 1). */
    public function onlyForSale(): static
    {
        $this->onlyForSale = true;

        return $this;
    }

    /** Restrict to products available for purchase (is_for_purchase = 1). */
    public function onlyForPurchase(): static
    {
        $this->onlyForPurchase = true;

        return $this;
    }

    /** Restrict to products that track lots (tracks_lots = 1). */
    public function onlyTracksLots(): static
    {
        $this->onlyTracksLots = true;

        return $this;
    }

    /** Restrict to products that track serial numbers (tracks_serials = 1). */
    public function onlyTracksSerials(): static
    {
        $this->onlyTracksSerials = true;

        return $this;
    }

    /** Include inactive products in results. */
    public function includeInactive(): static
    {
        $this->onlyActive = false;

        return $this;
    }

    private function buildLabel(Product $product): string
    {
        $base = "[{$product->code}] {$product->name}";

        if (! $this->showDetails) {
            return $base;
        }

        $parts = [];

        if ($product->relationLoaded('unit') && $product->unit) {
            $parts[] = $product->unit->code;
        }

        if (isset($product->sale_price)) {
            $parts[] = number_format((float) $product->sale_price, 2);
        }

        return $parts !== [] ? "{$base} — ".implode(' · ', $parts) : $base;
    }
}
