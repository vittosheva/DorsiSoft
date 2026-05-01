<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Core\Models\Country;

final class CountrySeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $created = 0;
        $updated = 0;

        $country = Country::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Ecuador',
                'iso2' => 'EC',
                'iso3' => 'ECU',
                'phone_code' => '593',
                'created_by' => 1,
                'updated_by' => 1,
            ]
        );

        $this->tallyModelChange($country, $created, $updated);

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
