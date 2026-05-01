<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Database\Seeders\TaxDefinitionSeeder;
use Modules\Finance\Database\Seeders\TaxRuleSeeder;
use Modules\Finance\Database\Seeders\TaxSeeder;
use Modules\Finance\Database\Seeders\TaxWithholdingRateSeeder;

final class InventoryDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            // This is a finance module, but it has a dependency on the Finance module, so we need to seed the tax data here.
            TaxSeeder::class,
            TaxDefinitionSeeder::class,
            TaxWithholdingRateSeeder::class,
            TaxRuleSeeder::class,

            InventoryDocumentTypeSeeder::class,
            CategorySeeder::class,
            BrandSeeder::class,
            UnitSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
