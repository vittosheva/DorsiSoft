<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Product;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class ProductDeletionValidator
{
    public function validate(Product $product): void
    {
        $violations = $this->checkViolations($product);

        if ($violations->isNotEmpty()) {
            throw new UnprocessableEntityHttpException(
                __('Cannot delete product. Violations found: :violations',
                    ['violations' => $violations->implode(', ')])
            );
        }
    }

    /**
     * @return Collection<int, string>
     */
    private function checkViolations(Product $product)
    {
        $violations = collect();

        if ($this->hasInventoryMovements($product)) {
            $violations->push(__('Product has inventory movements'));
        }

        if ($this->hasActiveLots($product)) {
            $violations->push(__('Product has active lots'));
        }

        if ($this->hasSerialNumbers($product)) {
            $violations->push(__('Product has serial numbers assigned'));
        }

        if ($this->hasActiveReservations($product)) {
            $violations->push(__('Product has active reservations'));
        }

        if ($this->hasStockBalance($product)) {
            $violations->push(__('Product has stock in warehouses'));
        }

        if ($this->hasPendingSalesOrders($product)) {
            $violations->push(__('Product is in pending sales orders'));
        }

        if ($this->hasIssuedDocuments($product)) {
            $violations->push(__('Product is in fiscal documents (invoices, notes)'));
        }

        if ($this->hasActiveQuotations($product)) {
            $violations->push(__('Product is in active quotations'));
        }

        return $violations;
    }

    private function hasInventoryMovements(Product $product): bool
    {
        return DB::table('inv_movements')
            ->where('product_id', $product->getKey())
            ->exists();
    }

    private function hasActiveLots(Product $product): bool
    {
        return DB::table('inv_lots')
            ->where('product_id', $product->getKey())
            ->where('is_active', true)
            ->exists();
    }

    private function hasSerialNumbers(Product $product): bool
    {
        return DB::table('inv_serials')
            ->where('product_id', $product->getKey())
            ->exists();
    }

    private function hasActiveReservations(Product $product): bool
    {
        return DB::table('inv_reservations')
            ->where('product_id', $product->getKey())
            ->where('status', '!=', 'cancelled')
            ->exists();
    }

    private function hasStockBalance(Product $product): bool
    {
        return DB::table('inv_balances')
            ->where('product_id', $product->getKey())
            ->where('quantity_available', '>', 0)
            ->exists();
    }

    private function hasPendingSalesOrders(Product $product): bool
    {
        return DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.order_id', '=', 'sales_orders.id')
            ->where('sales_order_items.product_id', $product->getKey())
            ->where('sales_orders.status', '!=', 'cancelled')
            ->where('sales_orders.deleted_at', null)
            ->exists();
    }

    private function hasIssuedDocuments(Product $product): bool
    {
        $tables = [
            'sales_invoice_items',
            'sales_credit_note_items',
            'sales_debit_note_items',
            'sales_delivery_guide_items',
        ];

        foreach ($tables as $table) {
            if (DB::table($table)->where('product_id', $product->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    private function hasActiveQuotations(Product $product): bool
    {
        return DB::table('sales_quotation_items')
            ->join('sales_quotations', 'sales_quotation_items.quotation_id', '=', 'sales_quotations.id')
            ->where('sales_quotation_items.product_id', $product->getKey())
            ->where('sales_quotations.status', '!=', 'expired')
            ->where('sales_quotations.status', '!=', 'rejected')
            ->exists();
    }
}
