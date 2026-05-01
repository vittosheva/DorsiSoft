<?php

declare(strict_types=1);

namespace Modules\Sales\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Modules\Finance\Models\Tax;
use Modules\Sales\Services\ItemTaxComputationService;
use Modules\Sales\Support\ItemTaxTypeGuard;

/**
 * Manages a pending (in-memory) item list for document Livewire components.
 *
 * Items are stored in $pendingItems and only synced to the database when the
 * consuming Filament page dispatches the persist event via DispatchesItemsPersistEvent.
 *
 * Consuming classes must declare these public properties:
 *   - public array $expandedItems = []
 */
trait HasPendingItems
{
    /** @var array<int, array<string, mixed>> */
    public array $pendingItems = [];

    /** @var array<string, string> */
    public array $itemTaxErrors = [];

    /**
     * Populate $pendingItems from the document's existing DB items.
     */
    public function loadFromDatabase(Model $document): void
    {
        $this->pendingItems = [];
        $this->expandedItems = [];
        $this->itemTaxErrors = [];

        foreach ($document->items as $item) {
            $taxes = [];

            foreach ($item->taxes as $tax) {
                $taxes[] = $this->makePendingTaxFromSnapshot($tax, (string) $tax->getKey(), $tax->getKey());
            }

            $this->pendingItems[] = [
                '_key' => (string) $item->getKey(),
                'db_id' => $item->getKey(),
                'product_id' => $item->product_id,
                'product_code' => $item->product_code,
                'product_name' => $item->product_name,
                'product_unit' => $item->product_unit,
                'sort_order' => $item->sort_order,
                'description' => $item->description,
                'detail_1' => $item->detail_1 ?? null,
                'detail_2' => $item->detail_2 ?? null,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_type' => $item->getRawOriginal('discount_type'),
                'discount_value' => $item->discount_value !== null ? (float) $item->discount_value : null,
                'discount_amount' => (float) $item->discount_amount,
                'tax_amount' => (float) $item->tax_amount,
                'subtotal' => (float) $item->subtotal,
                'total' => (float) $item->total,
                'taxes' => $taxes,
            ];
        }

        $this->dispatchPendingItemsCountUpdate();
    }

    /**
     * Append a new pending item (with optional initial taxes).
     *
     * @param  array<string, mixed>  $itemData
     * @param  array<int, array<string, mixed>>  $taxes
     */
    public function addPendingItem(array $itemData, array $taxes = []): void
    {
        $key = Str::uuid()->toString();
        $sortOrder = count($this->pendingItems)
            ? max(array_column($this->pendingItems, 'sort_order')) + 1
            : 1;

        $processedTaxes = array_map(
            fn (array $t) => array_merge($t, [
                '_key' => Str::uuid()->toString(),
                'db_id' => null,
            ]),
            $taxes,
        );

        $this->pendingItems[] = array_merge(
            [
                'discount_type' => 'percentage',
                'discount_value' => null,
                'discount_amount' => 0.0,
                'tax_amount' => 0.0,
                'subtotal' => 0.0,
                'total' => 0.0,
                'detail_1' => null,
                'detail_2' => null,
            ],
            $itemData,
            [
                '_key' => $key,
                'db_id' => null,
                'sort_order' => $sortOrder,
                'taxes' => $processedTaxes,
            ],
        );

        $this->recalculatePendingItemTotals($key);
        $this->expandedItems[$key] = false;
        unset($this->itemTaxErrors[$key]);
        $this->dispatchPendingItemsCountUpdate();
    }

    /**
     * Update a single field on a pending item identified by its _key.
     */
    public function updatePendingField(string $key, string $field, mixed $value): void
    {
        $index = $this->findPendingItemIndex($key);

        if ($index === -1) {
            return;
        }

        $this->pendingItems[$index][$field] = $value ?: null;
        $this->recalculatePendingItemTotals($key);
        $this->dispatchPendingItemsCountUpdate();
    }

    /**
     * Add a tax to a pending item; ignores duplicates by tax_id.
     *
     * @param  array<string, mixed>  $taxData
     */
    public function addPendingTax(string $itemKey, array $taxData): void
    {
        $index = $this->findPendingItemIndex($itemKey);

        if ($index === -1) {
            return;
        }

        foreach ($this->pendingItems[$index]['taxes'] as $existing) {
            if ($existing['tax_id'] === $taxData['tax_id']) {
                $this->setPendingTaxError($itemKey, __('This tax is already applied to the item.'));

                return;
            }
        }

        if ($this->pendingItemAlreadyContainsTaxType($itemKey, $taxData['tax_type'] ?? null)) {
            $taxType = $this->normalizePendingTaxType($taxData['tax_type'] ?? null) ?? __('tax');

            $this->setPendingTaxError(
                $itemKey,
                __('Only one :type tax may be applied to the same item.', ['type' => $taxType]),
            );

            return;
        }

        $this->pendingItems[$index]['taxes'][] = array_merge($taxData, [
            '_key' => Str::uuid()->toString(),
            'db_id' => null,
        ]);

        unset($this->itemTaxErrors[$itemKey]);
        $this->recalculatePendingItemTotals($itemKey);
        $this->dispatchPendingItemsCountUpdate();
    }

    /**
     * Remove a tax from a pending item by its _key.
     */
    public function removePendingTax(string $itemKey, string $taxKey): void
    {
        $index = $this->findPendingItemIndex($itemKey);

        if ($index === -1) {
            return;
        }

        $this->pendingItems[$index]['taxes'] = array_values(
            array_filter(
                $this->pendingItems[$index]['taxes'],
                fn (array $t) => $t['_key'] !== $taxKey,
            ),
        );

        unset($this->itemTaxErrors[$itemKey]);
        $this->recalculatePendingItemTotals($itemKey);
        $this->dispatchPendingItemsCountUpdate();
    }

    /**
     * Remove a pending item by its _key.
     */
    public function removePendingItem(string $key): void
    {
        $this->pendingItems = array_values(
            array_filter($this->pendingItems, fn (array $i) => $i['_key'] !== $key),
        );

        unset($this->expandedItems[$key]);
        unset($this->itemTaxErrors[$key]);
        $this->dispatchPendingItemsCountUpdate();
    }

    /**
     * Deep-clone a pending item, assigning new _keys and clearing db_ids.
     */
    public function duplicatePendingItem(string $key): void
    {
        $index = $this->findPendingItemIndex($key);

        if ($index === -1) {
            return;
        }

        $original = $this->pendingItems[$index];
        $newKey = Str::uuid()->toString();
        $sortOrder = count($this->pendingItems)
            ? max(array_column($this->pendingItems, 'sort_order')) + 1
            : 1;

        $newTaxes = array_map(
            fn (array $t) => array_merge($t, [
                '_key' => Str::uuid()->toString(),
                'db_id' => null,
            ]),
            $original['taxes'],
        );

        $this->pendingItems[] = array_merge($original, [
            '_key' => $newKey,
            'db_id' => null,
            'sort_order' => $sortOrder,
            'taxes' => $newTaxes,
        ]);

        $this->expandedItems[$newKey] = false;
        unset($this->itemTaxErrors[$newKey]);
        $this->dispatchPendingItemsCountUpdate();
    }

    public function canAddPendingTaxType(string $itemKey, mixed $taxType): bool
    {
        return ! $this->pendingItemAlreadyContainsTaxType($itemKey, $taxType);
    }

    /**
     * Recalculate totals for one pending item and its taxes in-memory.
     */
    public function recalculatePendingItemTotals(string $key): void
    {
        $index = $this->findPendingItemIndex($key);

        if ($index === -1) {
            return;
        }

        $item = &$this->pendingItems[$index];
        $quantity = (string) ($item['quantity'] ?? 0);
        $unitPrice = (string) ($item['unit_price'] ?? 0);
        $gross = bcmul($quantity, $unitPrice, 8);

        $discountAmount = '0.0000';

        if (($item['discount_value'] ?? null) !== null && ($item['discount_type'] ?? null) !== null) {
            if ($item['discount_type'] === 'percentage') {
                $discountAmount = bcmul($gross, bcdiv((string) $item['discount_value'], '100', 8), 4);
            } else {
                $discountAmount = bccomp((string) $item['discount_value'], $gross, 4) <= 0
                    ? (string) $item['discount_value']
                    : $gross;
            }
        }

        $subtotal = bcsub($gross, $discountAmount, 4);
        $computation = app(ItemTaxComputationService::class)->compute($quantity, $subtotal, $item['taxes'] ?? []);

        $item['taxes'] = array_map(function (array $tax): array {
            /** @var array<string, mixed> $source */
            $source = $tax['source'];

            return array_merge($source, [
                'tax_type' => $tax['tax_type'],
                'tax_code' => $tax['tax_code'],
                'tax_percentage_code' => $tax['tax_percentage_code'],
                'tax_rate' => (float) $tax['tax_rate'],
                'tax_calculation_type' => $tax['tax_calculation_type'],
                'base_amount' => (float) $tax['base_amount'],
                'tax_amount' => (float) $tax['tax_amount'],
            ]);
        }, $computation['taxes']);

        $item['discount_amount'] = (float) $discountAmount;
        $item['subtotal'] = (float) $subtotal;
        $item['tax_amount'] = (float) $computation['tax_amount'];
        $item['total'] = (float) $computation['total'];
    }

    /**
     * Live-computed totals from all pending items for the totals bar.
     *
     * @return array{subtotal: float, tax_amount: float, total: float, ice_amount: float, iva_amount: float}
     */
    #[Computed]
    public function pendingTotals(): array
    {
        $subtotal = '0.0000';
        $taxAmount = '0.0000';
        $iceAmount = '0.0000';
        $ivaAmount = '0.0000';

        foreach ($this->pendingItems as $item) {
            $subtotal = bcadd($subtotal, (string) $item['subtotal'], 4);
            $taxAmount = bcadd($taxAmount, (string) $item['tax_amount'], 4);

            foreach (($item['taxes'] ?? []) as $tax) {
                $taxType = app(ItemTaxTypeGuard::class)->normalizeType($tax['tax_type'] ?? null);

                if ($taxType === 'ICE') {
                    $iceAmount = bcadd($iceAmount, (string) ($tax['tax_amount'] ?? 0), 4);
                }

                if ($taxType === 'IVA') {
                    $ivaAmount = bcadd($ivaAmount, (string) ($tax['tax_amount'] ?? 0), 4);
                }
            }
        }

        return [
            'subtotal' => (float) $subtotal,
            'tax_amount' => (float) $taxAmount,
            'ice_amount' => (float) $iceAmount,
            'iva_amount' => (float) $ivaAmount,
            'total' => (float) bcadd($subtotal, $taxAmount, 4),
        ];
    }

    protected function dispatchPendingItemsCountUpdate(): void
    {
        if (method_exists($this, 'dispatchDocumentItemsCountUpdated')) {
            $this->dispatchDocumentItemsCountUpdated();
        }
    }

    protected function makePendingTaxFromCatalogTax(Tax $tax): array
    {
        return [
            'tax_id' => $tax->id,
            'tax_name' => $tax->name,
            'tax_type' => $tax->getRawOriginal('type'),
            'tax_code' => $tax->sri_code,
            'tax_percentage_code' => $tax->sri_percentage_code,
            'tax_rate' => (float) $tax->rate,
            'tax_calculation_type' => $tax->calculation_type?->value ?? $tax->getRawOriginal('calculation_type'),
            'base_amount' => 0.0,
            'tax_amount' => 0.0,
        ];
    }

    protected function makePendingTaxFromSnapshot(object $tax, ?string $key = null, ?int $dbId = null): array
    {
        return [
            '_key' => $key ?? Str::uuid()->toString(),
            'db_id' => $dbId,
            'tax_id' => $tax->tax_id,
            'tax_name' => $tax->tax_name,
            'tax_type' => $tax->getRawOriginal('tax_type'),
            'tax_code' => $tax->tax_code,
            'tax_percentage_code' => $tax->tax_percentage_code,
            'tax_rate' => (float) $tax->tax_rate,
            'tax_calculation_type' => $tax->tax_calculation_type?->value ?? $tax->getRawOriginal('tax_calculation_type'),
            'base_amount' => (float) $tax->base_amount,
            'tax_amount' => (float) $tax->tax_amount,
        ];
    }

    /**
     * @param  Collection<int, Tax>|iterable<Tax>  $taxes
     * @return list<array<string, mixed>>
     */
    protected function makePendingTaxesFromCatalog(iterable $taxes): array
    {
        return collect($taxes)
            ->map(fn (Tax $tax): array => $this->makePendingTaxFromCatalogTax($tax))
            ->all();
    }

    /**
     * Find the array index of a pending item by its _key. Returns -1 if not found.
     */
    private function findPendingItemIndex(string $key): int
    {
        foreach ($this->pendingItems as $index => $item) {
            if ($item['_key'] === $key) {
                return $index;
            }
        }

        return -1;
    }

    private function pendingItemAlreadyContainsTaxType(string $itemKey, mixed $taxType): bool
    {
        $index = $this->findPendingItemIndex($itemKey);

        if ($index === -1) {
            return false;
        }

        return app(ItemTaxTypeGuard::class)->containsType($this->pendingItems[$index]['taxes'] ?? [], $taxType);
    }

    private function normalizePendingTaxType(mixed $taxType): ?string
    {
        return app(ItemTaxTypeGuard::class)->normalizeType($taxType);
    }

    private function setPendingTaxError(string $itemKey, string $message): void
    {
        $this->itemTaxErrors[$itemKey] = $message;
    }
}
