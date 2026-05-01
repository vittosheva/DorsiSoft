<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Core\Models\State;

final class StateSeeder extends Seeder
{
    use ReportsSeederProgress;

    public function run(): void
    {
        $states = [
            'Azuay',
            'Bolívar',
            'Cañar',
            'Carchi',
            'Chimborazo',
            'Cotopaxi',
            'El Oro',
            'Esmeraldas',
            'Galápagos',
            'Guayas',
            'Imbabura',
            'Loja',
            'Los Ríos',
            'Manabí',
            'Morona Santiago',
            'Napo',
            'Orellana',
            'Pastaza',
            'Pichincha',
            'Santa Elena',
            'Santo Domingo de los Tsáchilas',
            'Sucumbíos',
            'Tungurahua',
            'Zamora Chinchipe',
        ];

        $created = 0;
        $updated = 0;

        foreach ($states as $state) {
            $model = State::query()->updateOrCreate(
                ['country_id' => 1, 'name' => $state],
                [
                    'created_by' => 1,
                    'updated_by' => 1,
                ]
            );

            $this->tallyModelChange($model, $created, $updated);
        }

        $this->reportCreatedAndUpdated($created, $updated);
    }
}
