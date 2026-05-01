<?php

declare(strict_types=1);

namespace Modules\Sales\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Inventory\Models\Product;

/**
 * Shared product search and item expand/collapse logic for document item Livewire components.
 */
trait SearchesProducts
{
    /** @var bool Indicates if a product search has been executed with enough characters. */
    public bool $hasSearchedProducts = false;

    public function updatedSearchQuery(): void
    {
        if (mb_strlen($this->searchQuery) < 2) {
            $this->hasSearchedProducts = false;
            $this->searchResults = [];

            return;
        }

        $this->hasSearchedProducts = true;

        $search = $this->searchQuery;
        $query = Product::query()
            ->select(['id', 'code', 'name', 'sale_price', 'unit_id']);

        if (mb_strlen($search) >= 3) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'ilike', '%'.$search.'%')
                    ->orWhere('code', 'ilike', '%'.$search.'%');
            });
        } else {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'ilike', $search.'%')
                    ->orWhere('code', 'ilike', $search.'%');
            });
        }

        $this->searchResults = $query
            ->with('unit:id,symbol')
            ->forSale()
            ->limit(10)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'sale_price' => $p->sale_price,
                'unit' => $p->unit?->symbol,
            ])
            ->toArray();
    }

    public function toggleExpand(string $key): void
    {
        $this->expandedItems[$key] = ! ($this->expandedItems[$key] ?? false);
    }
}
