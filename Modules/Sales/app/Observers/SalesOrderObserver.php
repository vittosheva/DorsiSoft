<?php

declare(strict_types=1);

namespace Modules\Sales\Observers;

use Modules\Core\Support\CustomerEmailNormalizer;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sales\Models\SalesOrder;

final class SalesOrderObserver
{
    public function saving(SalesOrder $salesOrder): void
    {
        $this->syncCustomerSnapshot($salesOrder);
        $this->syncSellerSnapshot($salesOrder);
    }

    private function syncCustomerSnapshot(SalesOrder $salesOrder): void
    {
        if (blank($salesOrder->business_partner_id)) {
            $salesOrder->customer_name = null;
            $salesOrder->customer_trade_name = null;
            $salesOrder->customer_identification_type = null;
            $salesOrder->customer_identification = null;
            $salesOrder->customer_address = null;
            $salesOrder->customer_email = null;
            $salesOrder->customer_phone = null;

            return;
        }

        if (! $salesOrder->isDirty('business_partner_id')
            && filled($salesOrder->customer_name)
            && filled($salesOrder->customer_identification_type)
            && filled($salesOrder->customer_identification)
            && filled($salesOrder->customer_address)) {
            return;
        }

        $bp = BusinessPartner::query()
            ->select(['id', 'legal_name', 'trade_name', 'identification_type', 'identification_number', 'tax_address', 'email', 'phone', 'mobile'])
            ->find($salesOrder->business_partner_id);

        if (! $bp) {
            return;
        }

        $salesOrder->customer_name = $bp->legal_name;
        $salesOrder->customer_trade_name = $bp->trade_name;
        $salesOrder->customer_identification_type = $bp->identification_type;
        $salesOrder->customer_identification = $bp->identification_number;
        $salesOrder->customer_address = $bp->tax_address;
        $salesOrder->customer_email = CustomerEmailNormalizer::normalizeAsString($bp->email);
        $salesOrder->customer_phone = $bp->phone ?? $bp->mobile;
    }

    private function syncSellerSnapshot(SalesOrder $salesOrder): void
    {
        if (! $salesOrder->isDirty('seller_id') && filled($salesOrder->seller_name)) {
            return;
        }

        $salesOrder->seller_name = blank($salesOrder->seller_id)
            ? null
            : User::query()->whereKey($salesOrder->seller_id)->value('name');
    }
}
