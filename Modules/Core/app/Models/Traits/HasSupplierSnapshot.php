<?php

declare(strict_types=1);

namespace Modules\Core\Models\Traits;

use Modules\People\Models\BusinessPartner;

/**
 * Provides denormalized supplier snapshot fields for transactional documents.
 *
 * Used by PurchaseOrder and any future supplier-facing documents.
 * Mirrors HasCustomerSnapshot but for supplier context.
 *
 * Rule: always read snapshot fields (supplier_name, etc.) for display — never JOIN via businessPartner FK.
 */
trait HasSupplierSnapshot
{
    /**
     * Populate all supplier snapshot fields from a BusinessPartner instance.
     * Also assigns business_partner_id for navigation purposes.
     */
    public function populateSupplierSnapshot(BusinessPartner $partner): void
    {
        $this->business_partner_id = $partner->getKey();
        $this->supplier_name = $partner->legal_name;
        $this->supplier_trade_name = $partner->trade_name;
        $this->supplier_identification_type = $partner->identification_type;
        $this->supplier_identification = $partner->identification_number;
        $this->supplier_address = $partner->tax_address;
        $this->supplier_email = $partner->email;
        $this->supplier_phone = $partner->phone ?? $partner->mobile;
    }

    /**
     * Returns true when the live BusinessPartner data differs from the stored snapshot on key fields.
     */
    public function isSnapshotStale(): bool
    {
        if (blank($this->business_partner_id)) {
            return false;
        }

        $partner = $this->businessPartner;

        if (! $partner) {
            return false;
        }

        return $this->supplier_name !== $partner->legal_name
            || $this->supplier_identification !== $partner->identification_number
            || $this->supplier_address !== $partner->tax_address;
    }

    /**
     * Re-populates the supplier snapshot from the current BusinessPartner data and saves.
     * Clears any cached PDF so the next generation reflects updated data.
     */
    public function refreshSnapshot(): void
    {
        $partner = $this->businessPartner;

        if (! $partner) {
            return;
        }

        $this->populateSupplierSnapshot($partner);
        $this->clearPdfMetadata();
        $this->save();
    }

    /**
     * Auto-populate the supplier snapshot before creation if supplier_name is missing.
     */
    protected static function bootHasSupplierSnapshot(): void
    {
        static::creating(function (self $model): void {
            if (filled($model->business_partner_id) && blank($model->supplier_name)) {
                $partner = BusinessPartner::find($model->business_partner_id);

                if ($partner) {
                    $model->populateSupplierSnapshot($partner);
                }
            }
        });
    }
}
