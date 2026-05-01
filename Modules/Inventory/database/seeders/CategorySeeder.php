<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Inventory\Models\Category;

final class CategorySeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed demo product categories for the first registered company.
     *
     * Categories are root-level (no parent). Code is generated manually via
     * DB::table() because WithoutModelEvents in DatabaseSeeder suppresses
     * the creating event used by HasAutoCode.
     * Account IDs are left null as the Chart of Accounts may not be seeded yet.
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('CategorySeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        $categories = [
            [
                'name' => 'Computadoras y Laptops',
                'description' => 'Equipos de cómputo portátiles y de escritorio',
            ],
            [
                'name' => 'Monitores y Pantallas',
                'description' => 'Pantallas para computadoras y señalización digital',
            ],
            [
                'name' => 'Periféricos',
                'description' => 'Teclados, ratones y accesorios de entrada',
            ],
            [
                'name' => 'Audio y Video',
                'description' => 'Auriculares, webcams y equipos multimedia',
            ],
            [
                'name' => 'Redes y Conectividad',
                'description' => 'Routers, switches y equipos de red',
            ],
        ];

        // Determine next sequential code from the current max
        $maxCode = DB::table('inv_categories')
            ->where('company_id', $companyId)
            ->where('code', 'LIKE', 'CAT%')
            ->max('code');

        $seq = $maxCode ? ((int) mb_substr($maxCode, 3)) + 1 : 1;

        $created = 0;

        foreach ($categories as $data) {
            $exists = DB::table('inv_categories')
                ->where('company_id', $companyId)
                ->where('name', $data['name'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $code = 'CAT'.mb_str_pad((string) $seq++, 3, '0', STR_PAD_LEFT);

            Category::create(array_merge($data, [
                'company_id' => $companyId,
                'code' => $code,
                'parent_id' => null,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]));

            $created++;
        }

        $this->reportCreated($created);
    }
}
