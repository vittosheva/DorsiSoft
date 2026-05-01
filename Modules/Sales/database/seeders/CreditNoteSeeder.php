<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Sales\Enums\CreditNoteReasonEnum;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Enums\SriPaymentMethodEnum;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\CreditNoteItem;
use Modules\Sales\Models\CreditNoteItemTax;
use Modules\Sales\Services\CreditNoteTotalsCalculator;

final class CreditNoteSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed 1 demo credit note for the first issued/paid invoice of the first company.
     *
     * Code format: NC-YEAR-NNNNNN (HasYearlyAutoCode). Generated manually
     * because WithoutModelEvents in DatabaseSeeder suppresses the creating event.
     *
     * Idempotency: if any credit note already exists for the company, the seeder
     * is skipped entirely.
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('CreditNoteSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        $hasCreditNotes = DB::table('sales_credit_notes')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasCreditNotes) {
            $this->reportSkipped('0 records created, records already exist');

            return;
        }

        // Find an issued or paid invoice to link the NC to
        $invoice = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first([
                'id',
                'company_id',
                'business_partner_id',
                'customer_name',
                'customer_trade_name',
                'customer_identification_type',
                'customer_identification',
                'customer_address',
                'customer_email',
                'customer_phone',
                'currency_code',
                'total',
                'establishment_code',
                'emission_point_code',
            ]);

        if (! $invoice) {
            $this->command->warn('CreditNoteSeeder: No issued/paid invoice found. Run InvoiceSeeder first. Skipping.');

            return;
        }

        // Find items of that invoice
        $items = DB::table('sales_invoice_items')
            ->select(['id', 'product_id', 'product_code', 'product_name', 'product_unit', 'description', 'quantity', 'unit_price', 'discount_amount', 'subtotal', 'tax_amount', 'total', 'sort_order'])
            ->where('invoice_id', $invoice->id)
            ->orderBy('sort_order')
            ->limit(1)
            ->get();

        if ($items->isEmpty()) {
            $this->command->warn('CreditNoteSeeder: Invoice has no items. Skipping.');

            return;
        }

        $establishmentCode = $invoice->establishment_code
            ?? DB::table('core_establishments')
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->value('code')
            ?? '001';

        $emissionPointCode = $invoice->emission_point_code
            ?? DB::table('core_emission_points')
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('code')
            ?? '001';

        $year = now()->year;
        $reasonCode = CreditNoteReasonEnum::ReturnOfGoods;

        $firstItem = $items->first();
        $invoiceItemTaxes = DB::table('sales_invoice_item_taxes')
            ->where('invoice_item_id', $firstItem->id)
            ->orderBy('id')
            ->get([
                'tax_id',
                'tax_name',
                'tax_type',
                'tax_code',
                'tax_percentage_code',
                'tax_rate',
                'tax_calculation_type',
                'base_amount',
                'tax_amount',
            ]);

        $calculator = app(CreditNoteTotalsCalculator::class);
        $customerEmail = filled($invoice->customer_email) ? [$invoice->customer_email] : null;
        $sequentialNumber = '000000001';
        $additionalInfo = array_values(array_filter([
            [
                'name' => 'Email',
                'value' => $invoice->customer_email,
            ],
            [
                'name' => 'Telefono',
                'value' => $invoice->customer_phone,
            ],
        ], static fn (array $item): bool => filled($item['value'])));

        $creditNote = CreditNote::create([
            'company_id' => $companyId,
            'code' => "NC-{$year}-000001",
            'establishment_code' => $establishmentCode,
            'emission_point_code' => $emissionPointCode,
            'sequential_number' => $sequentialNumber,
            'invoice_id' => $invoice->id,
            'business_partner_id' => $invoice->business_partner_id,
            'customer_name' => $invoice->customer_name,
            'customer_trade_name' => $invoice->customer_trade_name,
            'customer_identification_type' => $invoice->customer_identification_type,
            'customer_identification' => $invoice->customer_identification,
            'customer_address' => $invoice->customer_address,
            'customer_email' => $customerEmail,
            'customer_phone' => $invoice->customer_phone,
            'currency_code' => $invoice->currency_code,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'applied_amount' => '0.0000',
            'refunded_amount' => '0.0000',
            'status' => CreditNoteStatusEnum::Issued,
            'reason_code' => $reasonCode,
            'reason' => 'Demo credit note — product returned by customer',
            'access_key' => null,
            'sri_payments' => [],
            'additional_info' => $additionalInfo,
            'issue_date' => now()->subDays(2)->toDateString(),
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $creditNoteItem = CreditNoteItem::create([
            'credit_note_id' => $creditNote->getKey(),
            'product_id' => $firstItem->product_id,
            'product_code' => $firstItem->product_code,
            'product_name' => $firstItem->product_name,
            'product_unit' => $firstItem->product_unit,
            'sort_order' => 1,
            'description' => $firstItem->description,
            'quantity' => $firstItem->quantity,
            'unit_price' => $firstItem->unit_price,
            'discount_amount' => $firstItem->discount_amount,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);

        foreach ($invoiceItemTaxes as $invoiceItemTax) {
            CreditNoteItemTax::create([
                'credit_note_item_id' => $creditNoteItem->getKey(),
                'tax_id' => $invoiceItemTax->tax_id,
                'tax_name' => $invoiceItemTax->tax_name,
                'tax_type' => $invoiceItemTax->tax_type,
                'tax_code' => $invoiceItemTax->tax_code,
                'tax_percentage_code' => $invoiceItemTax->tax_percentage_code,
                'tax_rate' => $invoiceItemTax->tax_rate,
                'tax_calculation_type' => $invoiceItemTax->tax_calculation_type,
                'base_amount' => $invoiceItemTax->base_amount,
                'tax_amount' => $invoiceItemTax->tax_amount,
            ]);
        }

        $creditNote->load('items.taxes');
        $calculator->recalculate($creditNote);
        $creditNote->update([
            'sri_payments' => [[
                'method' => SriPaymentMethodEnum::BankTransfer->value,
                'amount' => (string) $creditNote->total,
            ]],
        ]);

        $this->reportCreated(1);
    }
}
