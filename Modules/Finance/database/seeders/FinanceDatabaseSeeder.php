<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\CashRegisterSeeder;
use Modules\Core\Database\Seeders\PaymentMethodSeeder;

final class FinanceDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            TaxCatalogSeeder::class,
            PriceListSeeder::class,
            PaymentMethodSeeder::class,
            CashRegisterSeeder::class,

            // CollectionSeeder::class, // Se ejecuta en InventoryDatabaseSeeder
            // TaxSeeder::class, // Se ejecuta en InventoryDatabaseSeeder
        ]);
    }
}
