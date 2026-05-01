<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Inventory\Enums\AbcClassificationEnum;
use Modules\Inventory\Enums\ProductTypeEnum;
use Modules\Inventory\Models\Product;

final class ProductSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed 10 realistic electronic products for the first registered company.
     *
     * FK IDs (category, brand, unit, tax) are resolved via DB::table() to bypass
     * TenantScope. Code is generated manually because WithoutModelEvents in
     * DatabaseSeeder suppresses the creating event used by HasAutoCode.
     *
     * Products 1–8: type=product, is_inventory=true
     * Products 9–10: type=service, is_inventory=false
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('ProductSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        // Resolve FK IDs bypassing TenantScope global scope
        $categories = DB::table('inv_categories')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->pluck('id');

        $brands = DB::table('inv_brands')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->pluck('id');

        $units = DB::table('inv_units')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->pluck('id');

        $taxes = DB::table('fin_taxes')
            ->select(['id', 'type', 'rate', 'is_default'])
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('is_default')
            ->orderByDesc('rate')
            ->orderBy('id')
            ->get();

        $positiveIvaTaxes = $taxes
            ->filter(static fn (object $tax): bool => $tax->type === 'IVA' && (float) $tax->rate > 0)
            ->values();

        $defaultSalesTaxId = $taxes->first(
            static fn (object $tax): bool => $tax->type === 'IVA' && (bool) $tax->is_default && (float) $tax->rate > 0
        )?->id ?? $positiveIvaTaxes->first()?->id ?? $taxes->first()?->id;

        $intermediateSalesTaxId = $positiveIvaTaxes->skip(1)->first()?->id ?? $defaultSalesTaxId;

        $reducedSalesTaxId = $positiveIvaTaxes->skip(2)->first()?->id ?? $intermediateSalesTaxId;

        $zeroRateTaxId = $taxes->first(
            static fn (object $tax): bool => $tax->type === 'IVA' && (float) $tax->rate === 0.0
        )?->id;

        $taxProfiles = [
            'standard' => $defaultSalesTaxId,
            'intermediate' => $intermediateSalesTaxId,
            'reduced' => $reducedSalesTaxId,
            'zero' => $zeroRateTaxId ?? $defaultSalesTaxId,
        ];

        // Required FKs — abort if missing
        if ($categories->isEmpty()) {
            $this->command->warn('ProductSeeder: No categories found. Run CategorySeeder first. Skipping.');

            return;
        }

        if ($units->isEmpty()) {
            $this->command->warn('ProductSeeder: No units found. Run UnitSeeder first. Skipping.');

            return;
        }

        if ($taxProfiles['standard'] === null || $taxProfiles['intermediate'] === null || $taxProfiles['reduced'] === null || $taxProfiles['zero'] === null) {
            $this->command->warn('ProductSeeder: Missing IVA demo taxes. Run TaxSeeder first. Skipping.');

            return;
        }

        $abcValues = [
            AbcClassificationEnum::A->value,
            AbcClassificationEnum::B->value,
            AbcClassificationEnum::C->value,
        ];

        $definitions = [
            // Physical products (type=product, is_inventory=true)
            [
                'name' => 'Laptop Lenovo ThinkPad X1 Carbon',
                'sku' => 'LEN-X1C-G11',
                'barcode' => '0195348889124',
                'type' => ProductTypeEnum::Product->value,
                'is_inventory' => true,
                'sale_price' => 1899.99,
                'standard_cost' => 1450.00,
                'current_unit_cost' => 1480.00,
                'min_stock' => 2.0,
                'max_stock' => 20.0,
                'reorder_point' => 5.0,
                'weight' => 1.12,
                'abc_classification' => $abcValues[array_rand($abcValues)],
                'tax_profile' => 'standard',
            ],
            [
                'name' => 'Monitor Samsung 27" 4K UHD',
                'sku' => 'SAM-U27-4K',
                'barcode' => '0887276714530',
                'type' => ProductTypeEnum::Product->value,
                'is_inventory' => true,
                'sale_price' => 459.99,
                'standard_cost' => 320.00,
                'current_unit_cost' => 325.00,
                'min_stock' => 3.0,
                'max_stock' => 30.0,
                'reorder_point' => 8.0,
                'weight' => 5.20,
                'abc_classification' => $abcValues[array_rand($abcValues)],
                'tax_profile' => 'standard',
            ],
            [
                'name' => 'Teclado Mecánico Logitech MX Keys',
                'sku' => 'LOG-MXKEYS-ES',
                'barcode' => '5099206086876',
                'type' => ProductTypeEnum::Product->value,
                'is_inventory' => true,
                'sale_price' => 129.99,
                'standard_cost' => 82.00,
                'current_unit_cost' => 84.00,
                'min_stock' => 5.0,
                'max_stock' => 50.0,
                'reorder_point' => 10.0,
                'weight' => 0.81,
                'abc_classification' => $abcValues[array_rand($abcValues)],
                'tax_profile' => 'intermediate',
            ],
            [
                'name' => 'Mouse Inalámbrico Logitech M720 Triathlon',
                'sku' => 'LOG-M720-BLK',
                'barcode' => '5099206066076',
                'type' => ProductTypeEnum::Product->value,
                'is_inventory' => true,
                'sale_price' => 79.99,
                'standard_cost' => 48.00,
                'current_unit_cost' => 49.50,
                'min_stock' => 5.0,
                'max_stock' => 60.0,
                'reorder_point' => 12.0,
                'weight' => 0.14,
                'abc_classification' => $abcValues[array_rand($abcValues)],
                'tax_profile' => 'reduced',
            ],
            [
                'name' => 'Auriculares Sony WH-1000XM5',
                'sku' => 'SON-WH1000XM5-BLK',
                'barcode' => '4548736132405',
                'type' => ProductTypeEnum::Product->value,
                'is_inventory' => true,
                'sale_price' => 349.99,
                'standard_cost' => 230.00,
                'current_unit_cost' => 235.00,
                'min_stock' => 2.0,
                'max_stock' => 25.0,
                'reorder_point' => 5.0,
                'weight' => 0.25,
                'abc_classification' => $abcValues[array_rand($abcValues)],
                'tax_profile' => 'reduced',
            ],
            [
                'name' => 'Webcam Logitech C920 Pro HD',
                'sku' => 'LOG-C920-PRO',
                'barcode' => '5099206064300',
                'type' => ProductTypeEnum::Product->value,
                'is_inventory' => true,
                'sale_price' => 89.99,
                'standard_cost' => 55.00,
                'current_unit_cost' => 56.00,
                'min_stock' => 5.0,
                'max_stock' => 40.0,
                'reorder_point' => 10.0,
                'weight' => 0.16,
                'abc_classification' => $abcValues[array_rand($abcValues)],
                'tax_profile' => 'intermediate',
            ],
            [
                'name' => 'Disco SSD Samsung 1TB NVMe 970 EVO Plus',
                'sku' => 'SAM-970EVO-1TB',
                'barcode' => '8806090396199',
                'type' => ProductTypeEnum::Product->value,
                'is_inventory' => true,
                'sale_price' => 109.99,
                'standard_cost' => 72.00,
                'current_unit_cost' => 73.50,
                'min_stock' => 5.0,
                'max_stock' => 50.0,
                'reorder_point' => 10.0,
                'weight' => 0.07,
                'abc_classification' => $abcValues[array_rand($abcValues)],
                'tax_profile' => 'zero',
            ],
            [
                'name' => 'Router TP-Link AX3000 Wi-Fi 6',
                'sku' => 'TPL-AX3000-V1',
                'barcode' => '6935364089788',
                'type' => ProductTypeEnum::Product->value,
                'is_inventory' => true,
                'sale_price' => 89.99,
                'standard_cost' => 55.00,
                'current_unit_cost' => 56.50,
                'min_stock' => 3.0,
                'max_stock' => 35.0,
                'reorder_point' => 7.0,
                'weight' => 0.56,
                'abc_classification' => $abcValues[array_rand($abcValues)],
                'tax_profile' => 'zero',
            ],

            // Services (type=service, is_inventory=false)
            [
                'name' => 'Soporte Técnico Premium Anual',
                'sku' => 'SRV-SOPORTE-ANU',
                'barcode' => null,
                'type' => ProductTypeEnum::Service->value,
                'is_inventory' => false,
                'sale_price' => 599.00,
                'standard_cost' => 0.00,
                'current_unit_cost' => 0.00,
                'min_stock' => null,
                'max_stock' => null,
                'reorder_point' => null,
                'weight' => null,
                'abc_classification' => null,
                'tax_profile' => 'zero',
            ],
            [
                'name' => 'Garantía Extendida 2 Años',
                'sku' => 'SRV-GARANTIA-2A',
                'barcode' => null,
                'type' => ProductTypeEnum::Service->value,
                'is_inventory' => false,
                'sale_price' => 199.00,
                'standard_cost' => 0.00,
                'current_unit_cost' => 0.00,
                'min_stock' => null,
                'max_stock' => null,
                'reorder_point' => null,
                'weight' => null,
                'abc_classification' => null,
                'tax_profile' => 'zero',
            ],
        ];

        // Determine next sequential code from the current max
        $maxCode = DB::table('inv_products')
            ->where('company_id', $companyId)
            ->where('code', 'LIKE', 'PRD%')
            ->max('code');

        $seq = $maxCode ? ((int) mb_substr($maxCode, 3)) + 1 : 1;

        $created = 0;
        $updated = 0;

        foreach ($definitions as $data) {
            $productId = DB::table('inv_products')
                ->where('company_id', $companyId)
                ->where('name', $data['name'])
                ->whereNull('deleted_at')
                ->value('id');

            $resolvedTaxId = $taxProfiles[$data['tax_profile']] ?? $taxProfiles['standard'];

            if ($productId !== null) {
                DB::table('inv_products')
                    ->where('id', $productId)
                    ->update([
                        'tax_id' => $resolvedTaxId,
                        'updated_by' => 1,
                        'updated_at' => now(),
                    ]);

                $updated++;

                continue;
            }

            $code = 'PRD'.mb_str_pad((string) $seq++, 3, '0', STR_PAD_LEFT);

            Product::create([
                'company_id' => $companyId,
                'code' => $code,
                'sku' => $data['sku'],
                'barcode' => $data['barcode'],
                'name' => $data['name'],
                'type' => $data['type'],
                'category_id' => $categories->random(),
                'brand_id' => $brands->isNotEmpty() ? $brands->random() : null,
                'unit_id' => $units->random(),
                'tax_id' => $resolvedTaxId,
                'is_inventory' => $data['is_inventory'],
                'is_for_sale' => true,
                'is_for_purchase' => $data['type'] === ProductTypeEnum::Product->value,
                'sale_price' => $data['sale_price'],
                'standard_cost' => $data['standard_cost'],
                'current_unit_cost' => $data['current_unit_cost'],
                'min_stock' => $data['min_stock'],
                'max_stock' => $data['max_stock'],
                'reorder_point' => $data['reorder_point'],
                'weight' => $data['weight'],
                'abc_classification' => $data['abc_classification'],
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $created++;
        }

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
