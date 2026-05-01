<?php

declare(strict_types=1);

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;

final class SystemDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            SystemAdminRoleSeeder::class,
            DocumentTypeSeeder::class,
        ]);
    }
}
