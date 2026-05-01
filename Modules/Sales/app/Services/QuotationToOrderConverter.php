<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sales\Enums\QuotationStatusEnum;
use Modules\Sales\Enums\SalesOrderStatusEnum;
use Modules\Sales\Events\QuotationAccepted;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use RuntimeException;

final class QuotationToOrderConverter
{
    /**
     * Convert an accepted quotation into a SalesOrder.
     *
     * @throws RuntimeException if the quotation is not in an acceptable state
     */
    public function convert(Quotation $quotation): SalesOrder
    {
        if (! in_array($quotation->status, [QuotationStatusEnum::Draft, QuotationStatusEnum::Sent], true)) {
            throw new RuntimeException("Cannot convert quotation [{$quotation->code}] with status [{$quotation->status->value}].");
        }

        $quotation->loadMissing(['items.taxes']);

        return DB::transaction(function () use ($quotation): SalesOrder {
            $order = SalesOrder::create([
                'company_id' => $quotation->company_id,
                'quotation_id' => $quotation->getKey(),
                'business_partner_id' => $quotation->business_partner_id,
                'customer_name' => $quotation->customer_name,
                'customer_trade_name' => $quotation->customer_trade_name,
                'customer_identification_type' => $quotation->customer_identification_type,
                'customer_identification' => $quotation->customer_identification,
                'customer_address' => $quotation->customer_address,
                'customer_email' => $quotation->customer_email,
                'customer_phone' => $quotation->customer_phone,
                'seller_id' => $quotation->seller_id,
                'seller_name' => $quotation->seller_name,
                'currency_code' => $quotation->currency_code,
                'status' => SalesOrderStatusEnum::Pending,
                'issue_date' => now()->toDateString(),
                'notes' => $quotation->notes,
                'subtotal' => $quotation->subtotal,
                'tax_base' => $quotation->tax_base,
                'discount_amount' => $quotation->discount_amount,
                'tax_amount' => $quotation->tax_amount,
                'total' => $quotation->total,
            ]);

            foreach ($quotation->items as $item) {
                $orderItem = SalesOrderItem::create([
                    'order_id' => $order->getKey(),
                    'product_id' => $item->product_id,
                    'product_code' => $item->product_code,
                    'product_name' => $item->product_name,
                    'product_unit' => $item->product_unit,
                    'sort_order' => $item->sort_order,
                    'description' => $item->description,
                    'detail_1' => $item->detail_1,
                    'detail_2' => $item->detail_2,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_type' => $item->discount_type?->value,
                    'discount_value' => $item->discount_value,
                    'discount_amount' => $item->discount_amount,
                    'tax_amount' => $item->tax_amount,
                    'subtotal' => $item->subtotal,
                    'total' => $item->total,
                ]);

                foreach ($item->taxes as $tax) {
                    $orderItem->taxes()->create([
                        'tax_id' => $tax->tax_id,
                        'tax_name' => $tax->tax_name,
                        'tax_type' => $tax->tax_type,
                        'tax_code' => $tax->tax_code,
                        'tax_percentage_code' => $tax->tax_percentage_code,
                        'tax_rate' => $tax->tax_rate,
                        'tax_calculation_type' => $tax->tax_calculation_type?->value ?? $tax->tax_calculation_type,
                        'base_amount' => $tax->base_amount,
                        'tax_amount' => $tax->tax_amount,
                    ]);
                }
            }

            $quotation->status = QuotationStatusEnum::Accepted;
            $quotation->accepted_at = now();
            $quotation->order_id = $order->getKey();
            $quotation->saveQuietly();

            QuotationAccepted::dispatch($quotation, $order);

            return $order;
        });
    }
}
