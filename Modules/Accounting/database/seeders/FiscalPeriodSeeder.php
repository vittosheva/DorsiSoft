<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Enums\FiscalPeriodStatusEnum;
use Modules\Accounting\Models\FiscalPeriod;

final class FiscalPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $companies = DB::table('core_companies')->get(['id']);
        $year = now()->year;
        $months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        $localeMonths = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        foreach ($companies as $company) {
            foreach ($months as $month) {
                $exists = FiscalPeriod::query()
                    ->where('company_id', $company->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $start = Carbon::create($year, $month, 1);
                $end = $start->copy()->endOfMonth();

                FiscalPeriod::create([
                    'company_id' => $company->id,
                    'year' => $year,
                    'month' => $month,
                    'name' => $localeMonths[$month].' '.$year,
                    'start_date' => $start,
                    'end_date' => $end,
                    'status' => FiscalPeriodStatusEnum::OPEN,
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);
            }
        }
    }
}
