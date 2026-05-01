<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\Company;
use Modules\Sales\Models\TaxWithholdingRule;

final class TaxWithholdingRulesSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if the target table does not exist yet (migrations not run)
        if (! Schema::hasTable('sales_tax_withholding_rules')) {
            return;
        }

        $defaults = [
            ['type' => 'IVA', 'concept' => 'iva', 'percentage' => '100.00', 'account' => null],
            ['type' => 'renta', 'concept' => 'servicios', 'percentage' => '3.00', 'account' => null],
        ];

        Company::all()->each(function (Company $company) use ($defaults): void {
            foreach ($defaults as $d) {
                TaxWithholdingRule::updateOrCreate([
                    'company_id' => $company->getKey(),
                    'type' => $d['type'],
                    'concept' => $d['concept'],
                ], array_merge($d, ['company_id' => $company->getKey()]));
            }
        });
    }
}
