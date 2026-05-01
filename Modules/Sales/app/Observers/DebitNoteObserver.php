<?php

declare(strict_types=1);

namespace Modules\Sales\Observers;

use Modules\Core\Support\CustomerEmailNormalizer;
use Modules\Finance\Models\TaxApplication;
use Modules\People\Models\BusinessPartner;
use Modules\Sales\Enums\DebitNoteStatusEnum;
use Modules\Sales\Models\DebitNote;
use Modules\Sri\Enums\ElectronicStatusEnum;

final class DebitNoteObserver
{
    public function saving(DebitNote $debitNote): void
    {
        $this->syncCustomerSnapshot($debitNote);
    }

    public function updated(DebitNote $debitNote): void
    {
        if ($debitNote->isDirty('electronic_status') && $debitNote->electronic_status === ElectronicStatusEnum::Authorized) {
            $this->createTaxApplications($debitNote);
        }

        if ($debitNote->isDirty('status') && $debitNote->status === DebitNoteStatusEnum::Voided) {
            TaxApplication::query()
                ->where('applicable_type', DebitNote::class)
                ->where('applicable_id', $debitNote->id)
                ->delete();
        }
    }

    private function createTaxApplications(DebitNote $debitNote): void
    {
        TaxApplication::query()
            ->where('applicable_type', DebitNote::class)
            ->where('applicable_id', $debitNote->id)
            ->delete();

        $debitNote->loadMissing(['items.taxes']);

        $companyId = $debitNote->company_id;
        $appliedAt = $debitNote->issue_date ?? now()->toDateString();

        foreach ($debitNote->items as $item) {
            foreach ($item->taxes as $taxLine) {
                TaxApplication::create([
                    'company_id' => $companyId,
                    'applicable_type' => DebitNote::class,
                    'applicable_id' => $debitNote->id,
                    'tax_id' => $taxLine->tax_id,
                    'tax_definition_id' => null,
                    'tax_type' => $taxLine->tax_type,
                    'sri_code' => $taxLine->tax_code,
                    'sri_percentage_code' => $taxLine->tax_percentage_code,
                    'base_amount' => $taxLine->base_amount,
                    'rate' => $taxLine->tax_rate,
                    'tax_amount' => $taxLine->tax_amount,
                    'calculation_type' => $taxLine->tax_calculation_type,
                    'snapshot' => [
                        'debit_note_id' => $debitNote->id,
                        'debit_note_code' => $debitNote->code,
                        'item_id' => $item->id,
                        'product_name' => $item->product_name ?? $item->description,
                        'tax_name' => $taxLine->tax_name,
                    ],
                    'applied_at' => $appliedAt,
                ]);
            }
        }
    }

    private function syncCustomerSnapshot(DebitNote $debitNote): void
    {
        if (blank($debitNote->business_partner_id)) {
            $debitNote->customer_name = null;
            $debitNote->customer_trade_name = null;
            $debitNote->customer_identification_type = null;
            $debitNote->customer_identification = null;
            $debitNote->customer_address = null;
            $debitNote->customer_email = null;
            $debitNote->customer_phone = null;

            return;
        }

        if (! $debitNote->isDirty('business_partner_id')
            && filled($debitNote->customer_name)
            && filled($debitNote->customer_identification_type)
            && filled($debitNote->customer_identification)
            && filled($debitNote->customer_address)) {
            return;
        }

        $bp = BusinessPartner::query()
            ->select(['id', 'legal_name', 'trade_name', 'identification_type', 'identification_number', 'tax_address', 'email', 'phone', 'mobile'])
            ->find($debitNote->business_partner_id);

        if (! $bp) {
            return;
        }

        $debitNote->customer_name = $bp->legal_name;
        $debitNote->customer_trade_name = $bp->trade_name;
        $debitNote->customer_identification_type = $bp->identification_type;
        $debitNote->customer_identification = $bp->identification_number;
        $debitNote->customer_address = $bp->tax_address;
        $debitNote->customer_email = CustomerEmailNormalizer::normalizeAsString($bp->email);
        $debitNote->customer_phone = $bp->phone ?? $bp->mobile;
    }
}
