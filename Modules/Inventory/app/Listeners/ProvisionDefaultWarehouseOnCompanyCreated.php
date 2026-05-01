<?php

declare(strict_types=1);

namespace Modules\Inventory\Listeners;

use Modules\Core\Events\CompanyCreated;
use Modules\Inventory\Models\Warehouse;

final class ProvisionDefaultWarehouseOnCompanyCreated
{
    public function handle(CompanyCreated $event): void
    {
        Warehouse::create([
            'company_id' => $event->company->getKey(),
            'code' => 'BOD001',
            'name' => __('Main Warehouse'),
            'is_default' => true,
            'is_active' => true,
        ]);
    }
}
