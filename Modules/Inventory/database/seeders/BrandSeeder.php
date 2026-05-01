<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Inventory\Models\Brand;

final class BrandSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed demo product brands for the first registered company.
     *
     * Brand does not use HasAutoCode — it has no code field.
     * Existence is checked via DB::table() to bypass TenantScope.
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('BrandSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        $brands = [
            'Lenovo',
            'Samsung',
            'Logitech',
            'Sony',
            'TP-Link',
        ];

        $created = 0;

        foreach ($brands as $name) {
            $exists = DB::table('inv_brands')
                ->where('company_id', $companyId)
                ->where('name', $name)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            Brand::create([
                'company_id' => $companyId,
                'name' => $name,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $created++;
        }

        $this->reportCreated($created);
    }
}
