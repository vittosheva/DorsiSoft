<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Enums\SalesOrderStatusEnum;
use Modules\Sales\Enums\SriPaymentMethodEnum;
use Modules\Sales\Exceptions\OrderAlreadyInvoicedException;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\InvoiceItem;
use Modules\Sales\Models\InvoiceItemTax;
use Modules\Sales\Models\SalesOrder;
use RuntimeException;

final class SalesOrderToInvoiceConverter
{
    public function __construct(private readonly InvoiceTotalsCalculator $calculator) {}

    /**
     * Convert a sales order into a Draft Invoice.
     *
     * @param  array{establishment_code?: ?string, emission_point_code?: ?string, sequential_number?: ?string}  $invoiceAttributes
     *
     * @throws RuntimeException if the order is not in a convertible status.
     */
    public function convert(SalesOrder $order, array $invoiceAttributes = []): Invoice
    {
        if (! in_array($order->status, [SalesOrderStatusEnum::Pending, SalesOrderStatusEnum::Confirmed], true)) {
            throw new RuntimeException(
                "Cannot invoice order [{$order->code}] with status [{$order->status->value}]."
            );
        }

        $order->loadMissing('items.taxes');
        $sequenceAttributes = $this->resolveSequenceAttributes($invoiceAttributes);

        return DB::transaction(function () use ($order, $sequenceAttributes): Invoice {
            // Lock row to prevent concurrent invoice creation for the same order
            $lockedOrder = SalesOrder::withoutGlobalScopes()->lockForUpdate()->findOrFail($order->getKey());

            if (Invoice::where('sales_order_id', $lockedOrder->getKey())->exists()) {
                throw new OrderAlreadyInvoicedException($lockedOrder);
            }

            $invoice = Invoice::create([
                'company_id' => $order->company_id,
                'sales_order_id' => $order->getKey(),
                'business_partner_id' => $order->business_partner_id,
                'customer_name' => $order->customer_name,
                'customer_trade_name' => $order->customer_trade_name,
                'customer_identification_type' => $order->customer_identification_type,
                'customer_identification' => $order->customer_identification,
                'customer_address' => $order->customer_address,
                'customer_email' => $order->customer_email,
                'customer_phone' => $order->customer_phone,
                'seller_id' => $order->seller_id,
                'seller_name' => $order->seller_name,
                'currency_code' => $order->currency_code,
                'status' => InvoiceStatusEnum::Draft,
                'issue_date' => now()->toDateString(),
                'subtotal' => 0,
                'tax_base' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'establishment_code' => $sequenceAttributes['establishment_code'],
                'emission_point_code' => $sequenceAttributes['emission_point_code'],
                'sequential_number' => $sequenceAttributes['sequential_number'],
            ]);

            foreach ($order->items as $item) {
                $invoiceItem = InvoiceItem::create([
                    'invoice_id' => $invoice->getKey(),
                    'product_id' => $item->product_id,
                    'product_code' => $item->product_code,
                    'product_name' => $item->product_name,
                    'product_unit' => $item->product_unit,
                    'sort_order' => $item->sort_order,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_type' => $item->discount_type?->value,
                    'discount_value' => $item->discount_value,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'subtotal' => 0,
                    'total' => 0,
                ]);

                foreach ($item->taxes as $tax) {
                    InvoiceItemTax::create([
                        'invoice_item_id' => $invoiceItem->getKey(),
                        'tax_id' => $tax->tax_id,
                        'tax_name' => $tax->tax_name,
                        'tax_type' => $tax->tax_type,
                        'tax_code' => $tax->tax_code,
                        'tax_percentage_code' => $tax->tax_percentage_code,
                        'tax_rate' => $tax->tax_rate,
                        'tax_calculation_type' => $tax->tax_calculation_type?->value ?? $tax->tax_calculation_type,
                        'base_amount' => 0,
                        'tax_amount' => 0,
                    ]);
                }
            }

            // Recalculate all totals with a fresh load
            $invoice = Invoice::with(['items.taxes'])->findOrFail($invoice->getKey());
            $this->calculator->recalculate($invoice);

            $invoice->refresh();
            $invoice->sri_payments = [[
                'method' => SriPaymentMethodEnum::default()->value,
                'amount' => round((float) $invoice->total, 2),
            ]];
            $invoice->saveQuietly();

            return $invoice->fresh();
        });
    }

    /**
     * @param  array{establishment_code?: ?string, emission_point_code?: ?string, sequential_number?: ?string, sequence_emission?: array{establishment_code?: ?string, emission_point_code?: ?string, sequential_number?: ?string}}  $invoiceAttributes
     * @return array{establishment_code: ?string, emission_point_code: ?string, sequential_number: ?string}
     */
    private function resolveSequenceAttributes(array $invoiceAttributes): array
    {
        return [
            'establishment_code' => data_get($invoiceAttributes, 'sequence_emission.establishment_code', $invoiceAttributes['establishment_code'] ?? null),
            'emission_point_code' => data_get($invoiceAttributes, 'sequence_emission.emission_point_code', $invoiceAttributes['emission_point_code'] ?? null),
            'sequential_number' => data_get($invoiceAttributes, 'sequence_emission.sequential_number', $invoiceAttributes['sequential_number'] ?? null),
        ];
    }
}
