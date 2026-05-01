<?php

declare(strict_types=1);

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Finance\Database\Seeders\CollectionSeeder;

final class SalesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            QuotationSeeder::class,
            SalesOrderSeeder::class,
            InvoiceSeeder::class,
            InvoiceTaxDemoBackfillSeeder::class,
            CreditNoteSeeder::class,
            DebitNoteSeeder::class,
            DeliveryGuideSeeder::class,
            WithholdingSeeder::class,
            TaxWithholdingRulesSeeder::class,
            PurchaseSettlementSeeder::class,

            // This is a finance module, but it has a dependency on the Finance module, so we need to seed the collection data here.
            CollectionSeeder::class,
        ]);
    }
}
