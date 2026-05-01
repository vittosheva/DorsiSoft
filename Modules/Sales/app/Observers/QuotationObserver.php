<?php

declare(strict_types=1);

namespace Modules\Sales\Observers;

use Modules\Core\Support\CustomerEmailNormalizer;
use Modules\Finance\Models\PriceList;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Models\Quotation;

final class QuotationObserver
{
    public function saving(Quotation $quotation): void
    {
        if ($quotation->issue_date && $quotation->validity_days) {
            $quotation->expires_at = $quotation->issue_date->addDays($quotation->validity_days);
        }

        $this->syncCustomerSnapshot($quotation);
        $this->syncSellerSnapshot($quotation);
        $this->syncPriceListSnapshot($quotation);
    }

    private function syncCustomerSnapshot(Quotation $quotation): void
    {
        if (blank($quotation->business_partner_id)) {
            $quotation->customer_name = null;
            $quotation->customer_trade_name = null;
            $quotation->customer_identification_type = null;
            $quotation->customer_identification = null;
            $quotation->customer_address = null;
            $quotation->customer_email = null;
            $quotation->customer_phone = null;

            return;
        }

        if (! $quotation->isDirty('business_partner_id')
            && filled($quotation->customer_name)
            && filled($quotation->customer_identification_type)
            && filled($quotation->customer_identification)
            && filled($quotation->customer_address)) {
            return;
        }

        $bp = BusinessPartner::query()
            ->select(['id', 'legal_name', 'trade_name', 'identification_type', 'identification_number', 'tax_address', 'email', 'phone', 'mobile'])
            ->find($quotation->business_partner_id);

        if (! $bp) {
            return;
        }

        $quotation->customer_name = $bp->legal_name;
        $quotation->customer_trade_name = $bp->trade_name;
        $quotation->customer_identification_type = $bp->identification_type;
        $quotation->customer_identification = $bp->identification_number;
        $quotation->customer_address = $bp->tax_address;
        $quotation->customer_email = CustomerEmailNormalizer::normalizeAsString($bp->email);
        $quotation->customer_phone = $bp->phone ?? $bp->mobile;
    }

    private function syncSellerSnapshot(Quotation $quotation): void
    {
        if (! $quotation->isDirty('seller_id') && filled($quotation->seller_name)) {
            return;
        }

        $quotation->seller_name = blank($quotation->seller_id)
            ? null
            : User::query()->whereKey($quotation->seller_id)->value('name');
    }

    private function syncPriceListSnapshot(Quotation $quotation): void
    {
        if (! $quotation->isDirty('price_list_id') && filled($quotation->price_list_name)) {
            return;
        }

        $quotation->price_list_name = blank($quotation->price_list_id)
            ? null
            : PriceList::query()->whereKey($quotation->price_list_id)->value('name');
    }
}
