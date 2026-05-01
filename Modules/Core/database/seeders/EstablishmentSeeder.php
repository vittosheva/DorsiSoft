<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Core\Models\Company;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\Establishment;

final class EstablishmentSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $created = 0;
        $updated = 0;

        $companies = Company::withoutGlobalScopes()->select(['id', 'tax_address', 'phone'])->get();

        foreach ($companies as $company) {
            $establishment = Establishment::withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => '001',
                ],
                [
                    'name' => 'Matriz',
                    'address' => $company->tax_address,
                    'phone' => $company->phone,
                    'is_main' => true,
                    'is_active' => true,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            $this->tallyModelChange($establishment, $created, $updated);

            $emissionPoint = EmissionPoint::withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'establishment_id' => $establishment->id,
                    'code' => '001',
                ],
                [
                    'name' => 'Principal',
                    'is_default' => true,
                    'is_active' => true,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            $this->tallyModelChange($emissionPoint, $created, $updated);
        }

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
