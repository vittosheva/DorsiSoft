<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Sales\Support\ItemTaxTypeGuard;

final class DocumentItemsPersistor
{
    /**
     * Sync pending items to the database.
     *
     * @param  array{item_model: class-string, tax_model: class-string, document_fk: string, item_tax_fk: string}  $config
     * @param  array<int, array<string, mixed>>  $pendingItems
     */
    public function sync(array $config, int $documentId, array $pendingItems): void
    {
        $itemModel = $config['item_model'];
        $taxModel = $config['tax_model'];
        $documentFk = $config['document_fk'];
        $itemTaxFk = $config['item_tax_fk'];

        $this->validatePendingItems($pendingItems);

        DB::transaction(function () use ($itemModel, $taxModel, $documentFk, $itemTaxFk, $documentId, $pendingItems): void {
            $keepItemIds = array_values(array_filter(array_column($pendingItems, 'db_id')));

            // Delete items the user removed
            $itemModel::where($documentFk, $documentId)
                ->when(
                    count($keepItemIds) > 0,
                    fn ($q) => $q->whereNotIn('id', $keepItemIds),
                )
                ->delete();

            foreach ($pendingItems as $pending) {
                $itemData = $this->extractItemFields($pending, $documentFk, $documentId);

                if ($pending['db_id'] !== null) {
                    $itemModel::where('id', $pending['db_id'])->update($itemData);
                    $itemId = (int) $pending['db_id'];
                } else {
                    $created = $itemModel::create($itemData);
                    $itemId = $created->getKey();
                }

                $this->syncItemTaxes($taxModel, $itemTaxFk, $itemId, $pending['taxes'] ?? []);
            }
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $taxes
     */
    private function syncItemTaxes(string $taxModel, string $itemTaxFk, int $itemId, array $taxes): void
    {
        $duplicateTypes = app(ItemTaxTypeGuard::class)->duplicateTypes($taxes);

        if ($duplicateTypes !== []) {
            throw ValidationException::withMessages([
                'pendingItems' => ['Each item may only contain one tax per type. Duplicated types: '.implode(', ', $duplicateTypes).'.'],
            ]);
        }

        $keepTaxIds = array_values(array_filter(array_column($taxes, 'db_id')));

        $taxModel::where($itemTaxFk, $itemId)
            ->when(
                count($keepTaxIds) > 0,
                fn ($q) => $q->whereNotIn('id', $keepTaxIds),
            )
            ->delete();

        foreach ($taxes as $tax) {
            $taxData = [
                $itemTaxFk => $itemId,
                'tax_id' => $tax['tax_id'],
                'tax_name' => $tax['tax_name'],
                'tax_type' => $tax['tax_type'],
                'tax_code' => $tax['tax_code'] ?? null,
                'tax_percentage_code' => $tax['tax_percentage_code'] ?? null,
                'tax_rate' => $tax['tax_rate'],
                'tax_calculation_type' => $tax['tax_calculation_type'] ?? null,
                'base_amount' => $tax['base_amount'],
                'tax_amount' => $tax['tax_amount'],
            ];

            if ($tax['db_id'] !== null) {
                $taxModel::where('id', $tax['db_id'])->update($taxData);
            } else {
                $taxModel::create($taxData);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $pending
     * @return array<string, mixed>
     */
    private function extractItemFields(array $pending, string $documentFk, int $documentId): array
    {
        return [
            $documentFk => $documentId,
            'product_id' => $pending['product_id'],
            'product_code' => $pending['product_code'],
            'product_name' => $pending['product_name'],
            'product_unit' => $pending['product_unit'],
            'sort_order' => $pending['sort_order'],
            'description' => $pending['description'],
            'detail_1' => $pending['detail_1'] ?? null,
            'detail_2' => $pending['detail_2'] ?? null,
            'quantity' => $pending['quantity'],
            'unit_price' => $pending['unit_price'],
            'discount_type' => $pending['discount_type'],
            'discount_value' => $pending['discount_value'],
            'discount_amount' => $pending['discount_amount'],
            'tax_amount' => $pending['tax_amount'],
            'subtotal' => $pending['subtotal'],
            'total' => $pending['total'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $pendingItems
     */
    private function validatePendingItems(array $pendingItems): void
    {
        $guard = app(ItemTaxTypeGuard::class);

        foreach ($pendingItems as $index => $pendingItem) {
            $duplicateTypes = $guard->duplicateTypes($pendingItem['taxes'] ?? []);

            if ($duplicateTypes === []) {
                continue;
            }

            $label = $pendingItem['product_name'] ?? $pendingItem['description'] ?? 'Item #'.($index + 1);

            throw ValidationException::withMessages([
                "pendingItems.{$index}.taxes" => ['The item "'.$label.'" contains duplicated tax types: '.implode(', ', $duplicateTypes).'.'],
            ]);
        }
    }
}
