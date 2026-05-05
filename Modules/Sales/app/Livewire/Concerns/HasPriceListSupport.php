<?php

declare(strict_types=1);

namespace Modules\Sales\Livewire\Concerns;

use Livewire\Attributes\On;
use Modules\Finance\Models\PriceListItem;
use Modules\Inventory\Models\Product;

trait HasPriceListSupport
{
    public ?int $priceListId = null;

    #[On('price-list-selected')]
    public function applyPriceList(?int $priceListId): void
    {
        if ($this->isReadOnly) {
            return;
        }

        $this->priceListId = $priceListId;

        if (empty($this->pendingItems)) {
            return;
        }

        $productIds = array_unique(array_filter(array_column($this->pendingItems, 'product_id')));

        if (empty($productIds)) {
            return;
        }

        $prices = $this->resolveProductPricesForIds($productIds, $priceListId);

        foreach ($this->pendingItems as $index => $item) {
            $productId = $item['product_id'] ?? null;

            if ($productId && array_key_exists($productId, $prices)) {
                $this->pendingItems[$index]['unit_price'] = $prices[$productId];
                $this->recalculatePendingItemTotals($item['_key']);
            }
        }

        $this->dispatchPendingItemsCountUpdate();
    }

    protected function resolveProductPrice(Product $product): float
    {
        if (! $this->priceListId) {
            return (float) ($product->sale_price ?? 0);
        }

        $priceListItem = PriceListItem::where('price_list_id', $this->priceListId)
            ->where('product_id', $product->id)
            ->orderBy('min_quantity')
            ->first();

        return (float) ($priceListItem?->price ?? $product->sale_price ?? 0);
    }

    /**
     * @param  int[]  $productIds
     * @return array<int, float>
     */
    private function resolveProductPricesForIds(array $productIds, ?int $priceListId): array
    {
        $products = Product::whereIn('id', $productIds)->get(['id', 'sale_price']);
        $prices = $products->pluck('sale_price', 'id')->map(fn ($p) => (float) ($p ?? 0))->all();

        if (! $priceListId) {
            return $prices;
        }

        $listItems = PriceListItem::where('price_list_id', $priceListId)
            ->whereIn('product_id', $productIds)
            ->orderBy('min_quantity')
            ->get(['product_id', 'price']);

        foreach ($listItems as $li) {
            $prices[$li->product_id] = (float) $li->price;
        }

        return $prices;
    }
}
