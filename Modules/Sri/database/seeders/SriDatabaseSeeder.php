<?php

declare(strict_types=1);

namespace Modules\Sri\Database\Seeders;

use Illuminate\Database\Seeder;

final class SriDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            DocumentSequenceSeeder::class,
            SriCatalogSeeder::class,
        ]);
    }
}
