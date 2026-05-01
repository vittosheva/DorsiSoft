<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Core\Models\CashRegister;

final class CashRegisterSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $created = 0;
        $updated = 0;

        $company = DB::table('core_companies')->select(['id'])->orderBy('id')->first();
        if (! $company) {
            $this->command?->warn('CashRegisterSeeder: No company found. Skipping.');

            return;
        }

        $registers = [
            'Caja Principal',
            'Caja Secundaria',
        ];

        foreach ($registers as $name) {
            $model = CashRegister::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $name,
                ],
                [
                    'is_active' => true,
                ],
            );
            $this->tallyModelChange($model, $created, $updated);
        }

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
