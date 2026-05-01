<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Inventory\Models\Unit;

final class UnitSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Seed demo units of measure for the first registered company.
     *
     * Code is generated manually via DB::table() because WithoutModelEvents
     * in DatabaseSeeder suppresses the creating event used by HasAutoCode.
     */
    public function run(): void
    {
        /** @var object{id: int}|null $company */
        $company = DB::table('core_companies')
            ->select(['id'])
            ->orderBy('id')
            ->first();

        if (! $company) {
            $this->command->warn('UnitSeeder: No company found. Skipping.');

            return;
        }

        $companyId = $company->id;

        $units = [
            ['name' => 'Unidad', 'symbol' => 'und'],
            ['name' => 'Par',    'symbol' => 'par'],
            ['name' => 'Caja',   'symbol' => 'caja'],
            ['name' => 'Kit',    'symbol' => 'kit'],
        ];

        // Determine next sequential code from the current max
        $maxCode = DB::table('inv_units')
            ->where('company_id', $companyId)
            ->where('code', 'LIKE', 'UNI%')
            ->max('code');

        $seq = $maxCode ? ((int) mb_substr($maxCode, 3)) + 1 : 1;

        $created = 0;

        foreach ($units as $data) {
            $exists = DB::table('inv_units')
                ->where('company_id', $companyId)
                ->where('name', $data['name'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $code = 'UNI'.mb_str_pad((string) $seq++, 3, '0', STR_PAD_LEFT);

            Unit::create(array_merge($data, [
                'company_id' => $companyId,
                'code' => $code,
                'is_active' => true,
                'created_by' => 1,
                'updated_by' => 1,
            ]));

            $created++;
        }

        $this->reportCreated($created);
    }
}
