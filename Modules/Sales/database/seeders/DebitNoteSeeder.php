<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Sales\Enums\DebitNoteStatusEnum;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Enums\SriPaymentMethodEnum;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\DebitNoteItem;
use Modules\Sales\Models\DebitNoteItemTax;
use Modules\Sales\Services\DebitNoteTotalsCalculator;
use Modules\Sales\Services\DocumentIssuanceService;

final class DebitNoteSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed demo debit notes for the first registered company.
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('DebitNoteSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        $hasDebitNotes = DB::table('sales_debit_notes')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasDebitNotes) {
            $this->reportSkipped('0 records created, records already exist');

            return;
        }

        // (Nota: Este seeder depende de invoices, pero si en el futuro requiere roles, usar PartnerRoleEnum)
        $invoices = DB::table('sales_invoices')
            ->where('company_id', $companyId)
            ->whereIn('status', [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->limit(2)
            ->get([
                'id',
                'business_partner_id',
                'customer_name',
                'customer_trade_name',
                'customer_identification_type',
                'customer_identification',
                'customer_address',
                'customer_email',
                'customer_phone',
                'currency_code',
                'establishment_code',
                'emission_point_code',
            ]);

        if ($invoices->isEmpty()) {
            $this->command->warn('DebitNoteSeeder: No issued/paid invoices found. Run InvoiceSeeder first. Skipping.');

            return;
        }

        $tax = DB::table('fin_taxes')
            ->select(['id', 'name', 'type', 'sri_code', 'sri_percentage_code', 'rate', 'calculation_type'])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        $defaultEstablishmentCode = DB::table('core_establishments')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('code') ?? '001';

        $defaultEmissionPointCode = DB::table('core_emission_points')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('code') ?? '001';

        $userId = DB::table('core_users')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id');

        $calculator = app(DebitNoteTotalsCalculator::class);
        $issuanceService = app(DocumentIssuanceService::class);
        $year = now()->year;

        $definitions = [
            [
                'status' => DebitNoteStatusEnum::Draft,
                'invoice_index' => 0,
                'sequential' => null,
                'reasons' => [
                    ['reason' => 'Gastos administrativos', 'value' => '12.50'],
                ],
                'sri_payments' => null,
            ],
            [
                'status' => DebitNoteStatusEnum::Issued,
                'invoice_index' => 1,
                'sequential' => '000000001',
                'reasons' => [
                    ['reason' => 'Interes por mora', 'value' => '25.00'],
                    ['reason' => 'Recargo logistico', 'value' => '8.00'],
                ],
                'sri_payments' => [[
                    'method' => SriPaymentMethodEnum::BankTransfer->value,
                    'amount' => null,
                ]],
            ],
        ];

        $created = 0;

        foreach ($definitions as $index => $definition) {
            $invoice = $invoices->get($definition['invoice_index']) ?? $invoices->first();

            if (! $invoice) {
                continue;
            }

            $customerEmail = self::normalizeCustomerEmail($invoice->customer_email);
            $establishmentCode = $invoice->establishment_code ?: $defaultEstablishmentCode;
            $emissionPointCode = $invoice->emission_point_code ?: $defaultEmissionPointCode;
            $code = 'ND-'.$year.'-'.mb_str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT);

            $debitNote = DebitNote::create([
                'company_id' => $companyId,
                'code' => $code,
                'invoice_id' => $invoice->id,
                'issue_date' => now()->toDateString(),
                'business_partner_id' => $invoice->business_partner_id,
                'customer_name' => $invoice->customer_name,
                'customer_trade_name' => $invoice->customer_trade_name,
                'customer_identification_type' => $invoice->customer_identification_type,
                'customer_identification' => $invoice->customer_identification,
                'customer_address' => $invoice->customer_address,
                'customer_email' => $customerEmail,
                'customer_phone' => $invoice->customer_phone,
                'currency_code' => $invoice->currency_code ?? 'USD',
                'reasons' => $definition['reasons'],
                'tax_id' => $tax?->id,
                'tax_name' => $tax?->name,
                'tax_rate' => $tax?->rate,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'status' => DebitNoteStatusEnum::Draft,
                'access_key' => null,
                'establishment_code' => $definition['status'] === DebitNoteStatusEnum::Issued ? $establishmentCode : null,
                'emission_point_code' => $definition['status'] === DebitNoteStatusEnum::Issued ? $emissionPointCode : null,
                'sequential_number' => $definition['sequential'],
                'sri_payments' => $definition['sri_payments'],
                'payment_method' => null,
                'payment_amount' => null,
                'additional_info' => [
                    ['key' => 'Origen', 'value' => 'Seeder demo'],
                ],
                'notes' => 'Nota de debito demo generada por seeder.',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($definition['reasons'] as $sortOrder => $reason) {
                $item = DebitNoteItem::create([
                    'debit_note_id' => $debitNote->getKey(),
                    'product_id' => null,
                    'product_code' => null,
                    'product_name' => mb_substr((string) ($reason['reason'] ?? __('Additional charge')), 0, 255),
                    'product_unit' => null,
                    'sort_order' => $sortOrder + 1,
                    'description' => $reason['reason'] ?? __('Additional charge'),
                    'detail_1' => null,
                    'detail_2' => null,
                    'quantity' => 1,
                    'unit_price' => $reason['value'] ?? 0,
                    'discount_type' => null,
                    'discount_value' => null,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'subtotal' => 0,
                    'total' => 0,
                ]);

                if ($tax) {
                    DebitNoteItemTax::create([
                        'debit_note_item_id' => $item->getKey(),
                        'tax_id' => $tax->id,
                        'tax_name' => $tax->name,
                        'tax_type' => $tax->type,
                        'tax_code' => $tax->sri_code,
                        'tax_percentage_code' => $tax->sri_percentage_code,
                        'tax_rate' => $tax->rate,
                        'tax_calculation_type' => $tax->calculation_type,
                        'base_amount' => 0,
                        'tax_amount' => 0,
                    ]);
                }
            }

            $debitNote = DebitNote::with(['items.taxes'])->findOrFail($debitNote->getKey());
            $calculator->recalculate($debitNote);

            if ($definition['status'] === DebitNoteStatusEnum::Issued) {
                $debitNote->update([
                    'sri_payments' => [[
                        'method' => SriPaymentMethodEnum::BankTransfer->value,
                        'amount' => (string) $debitNote->total,
                    ]],
                    'payment_method' => SriPaymentMethodEnum::BankTransfer->value,
                    'payment_amount' => (string) $debitNote->total,
                ]);
            }

            $created++;
        }

        $this->reportCreated($created);
    }

    /**
     * @return list<string>|null
     */
    private static function normalizeCustomerEmail(mixed $value): ?array
    {
        if (blank($value)) {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            }
        }

        $emails = is_array($value) ? $value : [$value];

        return collect($emails)
            ->filter(static fn (mixed $email): bool => filled($email))
            ->map(static fn (mixed $email): string => (string) $email)
            ->values()
            ->all();
    }
}
