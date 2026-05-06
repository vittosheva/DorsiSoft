<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Models\SaleNote;

final class SaleNoteToInvoiceConverter
{
    public function __construct(private readonly InvoiceTotalsCalculator $calculator) {}

    public function convert(SaleNote $saleNote): Invoice
    {
        return DB::transaction(function () use ($saleNote) {
            $invoice = Invoice::create([
                'company_id' => $saleNote->company_id,
                'business_partner_id' => $saleNote->business_partner_id,
                'customer_name' => $saleNote->customer_name,
                'customer_trade_name' => $saleNote->customer_trade_name,
                'customer_identification_type' => $saleNote->customer_identification_type,
                'customer_identification' => $saleNote->customer_identification,
                'customer_address' => $saleNote->customer_address,
                'customer_email' => $saleNote->customer_email,
                'customer_phone' => $saleNote->customer_phone,
                'seller_id' => $saleNote->seller_id,
                'seller_name' => $saleNote->seller_name,
                'currency_code' => $saleNote->currency_code,
                'issue_date' => $saleNote->issue_date,
                'notes' => $saleNote->notes,
                'status' => InvoiceStatusEnum::Draft,
            ]);

            foreach ($saleNote->items()->with('taxes')->get() as $saleNoteItem) {
                $invoiceItem = $invoice->items()->create([
                    'product_id' => $saleNoteItem->product_id,
                    'product_code' => $saleNoteItem->product_code,
                    'product_name' => $saleNoteItem->product_name,
                    'product_unit' => $saleNoteItem->product_unit,
                    'sort_order' => $saleNoteItem->sort_order,
                    'description' => $saleNoteItem->description,
                    'detail_1' => $saleNoteItem->detail_1,
                    'detail_2' => $saleNoteItem->detail_2,
                    'quantity' => $saleNoteItem->quantity,
                    'unit_price' => $saleNoteItem->unit_price,
                    'discount_type' => $saleNoteItem->discount_type,
                    'discount_value' => $saleNoteItem->discount_value,
                    'discount_amount' => $saleNoteItem->discount_amount,
                    'tax_amount' => $saleNoteItem->tax_amount,
                    'subtotal' => $saleNoteItem->subtotal,
                    'total' => $saleNoteItem->total,
                ]);

                $invoiceItem->taxes()->createMany(
                    $saleNoteItem->taxes->map(fn ($tax) => [
                        'tax_id' => $tax->tax_id,
                        'tax_name' => $tax->tax_name,
                        'tax_type' => $tax->tax_type,
                        'tax_code' => $tax->tax_code,
                        'tax_percentage_code' => $tax->tax_percentage_code,
                        'tax_calculation_type' => $tax->tax_calculation_type,
                        'tax_rate' => $tax->tax_rate,
                        'base_amount' => $tax->base_amount,
                        'tax_amount' => $tax->tax_amount,
                    ])->all()
                );
            }

            $this->calculator->recalculate($invoice);

            $saleNote->update(['converted_to_invoice_id' => $invoice->id]);

            return $invoice;
        });
    }
}
