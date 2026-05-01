<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;

final class AccountingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            FiscalPeriodSeeder::class,
        ]);
    }
}
