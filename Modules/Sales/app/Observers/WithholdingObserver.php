<?php

declare(strict_types=1);

namespace Modules\Sales\Observers;

use Modules\Finance\Models\TaxApplication;
use Modules\People\Models\BusinessPartner;
use Modules\Sales\Enums\WithholdingStatusEnum;
use Modules\Sales\Models\Withholding;
use Modules\Sales\Services\WithholdingCalculationService;
use Modules\Sri\Enums\ElectronicStatusEnum;

final class WithholdingObserver
{
    public function __construct(
        private readonly WithholdingCalculationService $calculationService,
    ) {}

    public function saving(Withholding $withholding): void
    {
        $this->syncSupplierSnapshot($withholding);
    }

    public function creating(Withholding $withholding): void
    {
        $withholding->status ??= 'draft';

        if (blank($withholding->period_fiscal) && $withholding->issue_date) {
            $withholding->period_fiscal = $withholding->issue_date->format('Y/m');
        }
    }

    public function created(Withholding $withholding): void
    {
        $this->calculationService->propagateSourceDocument($withholding);
    }

    public function updated(Withholding $withholding): void
    {
        if ($withholding->wasChanged(['source_document_type', 'source_document_number', 'source_document_date', 'source_purchase_settlement_id'])) {
            $this->calculationService->propagateSourceDocument($withholding);
        }

        if ($withholding->isDirty('electronic_status') && $withholding->electronic_status === ElectronicStatusEnum::Authorized) {
            $this->createTaxApplications($withholding);
        }

        if ($withholding->isDirty('status') && $withholding->status === WithholdingStatusEnum::Voided) {
            TaxApplication::query()
                ->where('applicable_type', Withholding::class)
                ->where('applicable_id', $withholding->id)
                ->delete();
        }
    }

    private function createTaxApplications(Withholding $withholding): void
    {
        TaxApplication::query()
            ->where('applicable_type', Withholding::class)
            ->where('applicable_id', $withholding->id)
            ->delete();

        $withholding->loadMissing(['items']);

        $companyId = $withholding->company_id;
        $appliedAt = $withholding->issue_date ?? now()->toDateString();

        foreach ($withholding->items as $item) {
            TaxApplication::create([
                'company_id' => $companyId,
                'applicable_type' => Withholding::class,
                'applicable_id' => $withholding->id,
                'tax_id' => null,
                'tax_definition_id' => null,
                'tax_type' => $item->tax_type,
                'sri_code' => $item->tax_code,
                'sri_percentage_code' => null,
                'base_amount' => $item->base_amount,
                'rate' => $item->tax_rate,
                'tax_amount' => $item->withheld_amount,
                'calculation_type' => null,
                'snapshot' => [
                    'withholding_id' => $withholding->id,
                    'withholding_code' => $withholding->code,
                    'item_id' => $item->id,
                    'tax_type' => $item->tax_type,
                    'tax_code' => $item->tax_code,
                ],
                'applied_at' => $appliedAt,
            ]);
        }
    }

    private function syncSupplierSnapshot(Withholding $withholding): void
    {
        if (blank($withholding->business_partner_id)) {
            $withholding->supplier_name = null;
            $withholding->supplier_identification_type = null;
            $withholding->supplier_identification = null;
            $withholding->supplier_address = null;

            return;
        }

        if (
            ! $withholding->isDirty('business_partner_id')
            && filled($withholding->supplier_name)
            && filled($withholding->supplier_identification_type)
            && filled($withholding->supplier_identification)
        ) {
            return;
        }

        $bp = BusinessPartner::query()
            ->select(['id', 'legal_name', 'identification_type', 'identification_number', 'tax_address'])
            ->find($withholding->business_partner_id);

        if (! $bp) {
            return;
        }

        $withholding->supplier_name = $bp->legal_name;
        $withholding->supplier_identification_type = $bp->identification_type;
        $withholding->supplier_identification = $bp->identification_number;
        $withholding->supplier_address = $bp->tax_address;
    }
}
