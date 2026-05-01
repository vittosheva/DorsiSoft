<?php

declare(strict_types=1);

namespace Modules\Core\Models\Traits;

use Modules\Core\Support\CustomerEmailNormalizer;
use Modules\People\Models\BusinessPartner;

/**
 * Provides denormalized customer snapshot fields for transactional documents.
 *
 * Used by Quotation, SalesOrder, Invoice, and any future customer-facing documents.
 * The snapshot captures customer data at the moment the document is created,
 * ensuring historical accuracy even if the BusinessPartner record changes later.
 *
 * Rule: always read snapshot fields (customer_name, etc.) for display — never JOIN via businessPartner FK.
 */
trait HasCustomerSnapshot
{
    /**
     * Populate all customer snapshot fields from a BusinessPartner instance.
     * Also assigns business_partner_id for navigation purposes.
     */
    public function populateCustomerSnapshot(BusinessPartner $partner): void
    {
        $this->business_partner_id = $partner->getKey();
        $this->customer_name = $partner->legal_name;
        $this->customer_trade_name = $partner->trade_name;
        $this->customer_identification_type = $partner->identification_type;
        $this->customer_identification = $partner->identification_number;
        $this->customer_address = $partner->tax_address;
        $this->customer_email = CustomerEmailNormalizer::normalizeForCast($partner->email, $this->getCasts()['customer_email'] ?? null);
        $this->customer_phone = $partner->phone ?? $partner->mobile;
    }

    /**
     * Returns true when the live BusinessPartner data differs from the stored snapshot on key fields.
     * Only meaningful for documents in an editable state — for authorized docs, staleness is expected and correct.
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

        return $this->customer_name !== $partner->legal_name
            || $this->customer_identification !== $partner->identification_number
            || $this->customer_address !== $partner->tax_address;
    }

    /**
     * Re-populates the customer snapshot from the current BusinessPartner data and saves.
     * Clears any cached PDF so the next generation reflects updated data.
     */
    public function refreshSnapshot(): void
    {
        $partner = $this->businessPartner;

        if (! $partner) {
            return;
        }

        $this->populateCustomerSnapshot($partner);
        $this->clearPdfMetadata();
        $this->save();
    }

    /**
     * Auto-populate the customer snapshot before creation if customer_name is missing.
     * Handles cases where the Filament form's hidden dehydrated fields are not passed
     * (e.g., Filament v5 may skip null hidden fields during dehydration).
     */
    protected static function bootHasCustomerSnapshot(): void
    {
        static::creating(function (self $model): void {
            if (filled($model->business_partner_id) && blank($model->customer_name)) {
                $partner = BusinessPartner::find($model->business_partner_id);

                if ($partner) {
                    $model->populateCustomerSnapshot($partner);
                }
            }
        });
    }
}
