<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\People\Models\User;

final class UserSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $created = 0;
        $updated = 0;

        $user = User::query()
            ->updateOrCreate(
                ['email' => 'vittoriodormi83@gmail.com'],
                [
                    'code' => 'ADM001',
                    'name' => 'Vittorio Dormi',
                    'phone' => '+593999999999',
                    'language' => 'es',
                    'timezone' => 'America/Guayaquil',
                    'is_allowed_to_login' => true,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'password' => Hash::make('Isa2108__'),
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

        $this->tallyModelChange($user, $created, $updated);

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
