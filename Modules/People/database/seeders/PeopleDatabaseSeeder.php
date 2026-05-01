<?php

declare(strict_types=1);

namespace Modules\People\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\EstablishmentSeeder;

final class PeopleDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            PeopleDemoUsersSeeder::class,
            TeamRolesSeeder::class,
            BusinessPartnerSeeder::class,
            EstablishmentSeeder::class,
        ]);

        if (config('people.plan_matrix.enabled')) {
            $this->call(RolePlanMatrixSeeder::class);
        }
    }
}
