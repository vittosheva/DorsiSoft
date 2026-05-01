<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Database\Seeders\AccountingDatabaseSeeder;
use Modules\Finance\Database\Seeders\FinanceDatabaseSeeder;
use Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder;
use Modules\People\Database\Seeders\CentralRolesSeeder;
use Modules\People\Database\Seeders\PeopleDatabaseSeeder;
use Modules\Sales\Database\Seeders\SalesDatabaseSeeder;
use Modules\Sri\Database\Seeders\SriDatabaseSeeder;
use Modules\System\Database\Seeders\SystemDatabaseSeeder;
use Modules\Workflow\Database\Seeders\WorkflowDatabaseSeeder;

final class CoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            // Modules
            CentralRolesSeeder::class,

            // Core
            UserSeeder::class,
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            CurrencySeeder::class,
            PartnerRoleSeeder::class,

            // Modules
            PeopleDatabaseSeeder::class,
            SystemDatabaseSeeder::class,
            InventoryDatabaseSeeder::class,
            FinanceDatabaseSeeder::class,
            SalesDatabaseSeeder::class,
            SriDatabaseSeeder::class,
            WorkflowDatabaseSeeder::class,
            AccountingDatabaseSeeder::class,
        ]);
    }
}
