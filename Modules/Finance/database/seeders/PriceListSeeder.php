<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Finance\Models\PriceList;
use Modules\Finance\Models\PriceListItem;

final class PriceListSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed two demo price lists with items for the first registered company.
     *
     * "Lista General" — standard prices (sale_price × 1.00), marked as default.
     * "Lista VIP"     — 10 % discount off sale_price (sale_price × 0.90).
     *
     * Price list items are created for every active, for-sale product.
     * Code is generated manually (prefix LP) because WithoutModelEvents in
     * DatabaseSeeder suppresses the creating event used by HasAutoCode.
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('PriceListSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        $products = DB::table('inv_products')
            ->select(['id', 'sale_price'])
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('is_for_sale', true)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($products->isEmpty()) {
            $this->command->warn('PriceListSeeder: No products found. Run ProductSeeder first. Skipping.');

            return;
        }

        /** @var array<int, array{name: string, description: string, currency_code: string, is_default: bool, price_factor: float}> $definitions */
        $definitions = [
            [
                'name' => 'Lista General',
                'description' => 'Lista de precios estándar para clientes generales',
                'currency_code' => 'USD',
                'is_default' => true,
                'price_factor' => 1.00,
            ],
            [
                'name' => 'Lista VIP',
                'description' => 'Lista de precios con 10 % de descuento para clientes preferentes',
                'currency_code' => 'USD',
                'is_default' => false,
                'price_factor' => 0.90,
            ],
        ];

        $maxCode = DB::table('sales_price_lists')
            ->where('company_id', $companyId)
            ->where('code', 'LIKE', 'LP%')
            ->max('code');

        $seq = $maxCode ? ((int) mb_substr($maxCode, 2)) + 1 : 1;

        $created = 0;

        foreach ($definitions as $data) {
            $exists = DB::table('sales_price_lists')
                ->where('company_id', $companyId)
                ->where('name', $data['name'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $code = 'LP'.mb_str_pad((string) $seq++, 3, '0', STR_PAD_LEFT);
            $factor = $data['price_factor'];

            $priceList = PriceList::create([
                'company_id' => $companyId,
                'code' => $code,
                'name' => $data['name'],
                'description' => $data['description'],
                'currency_code' => $data['currency_code'],
                'start_date' => null,
                'end_date' => null,
                'is_default' => $data['is_default'],
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            foreach ($products as $product) {
                $price = round((float) $product->sale_price * $factor, 2);

                PriceListItem::create([
                    'price_list_id' => $priceList->getKey(),
                    'product_id' => $product->id,
                    'price' => $price,
                    'min_quantity' => 1.0,
                ]);
            }

            $created++;
        }

        $this->reportCreated($created);
    }
}
