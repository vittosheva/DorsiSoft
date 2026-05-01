<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Core\Models\Currency;

final class CurrencySeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $timestamp = now();

        $defaults = [
            'is_active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ];

        $currencies = [
            ['code' => 'USD', 'name' => 'Dólar USA', 'symbol' => '$', 'is_default' => true],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => false],
            ['code' => 'COP', 'name' => 'Peso Colombiano', 'symbol' => '$', 'is_default' => false],
            ['code' => 'PEN', 'name' => 'Sol Peruano', 'symbol' => 'S/', 'is_default' => false],
            ['code' => 'MXN', 'name' => 'Peso Mexicano', 'symbol' => '$', 'is_default' => false],
        ];

        $records = array_map(
            fn (array $currency): array => array_merge($defaults, $currency, [
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]),
            $currencies,
        );

        $created = Currency::query()->insertOrIgnore($records);

        $this->reportCreated((int) $created);
    }
}
