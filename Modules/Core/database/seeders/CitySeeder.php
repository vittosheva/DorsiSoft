<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\Core\Models\City;
use Modules\Core\Models\State;

final class CitySeeder extends Seeder
{
    use ReportsSeederProgress;

    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        // $path = database_path('data/ecuador.json');
        $path = module_path('Core', 'Database/Data/ecuador.json');

        if (! file_exists($path)) {
            $this->command->error('ecuador.json no encontrado');

            return;
        }

        $data = json_decode(file_get_contents($path), true);

        // Cache state ids
        $stateMap = State::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtoupper($name) => $id])
            ->toArray();

        $created = 0;

        foreach ($data as $province) {

            $stateName = mb_strtoupper($province['name']);
            $stateId = $stateMap[$stateName] ?? null;

            if (! $stateId) {
                $this->command->warn("Provincia no encontrada: {$province['name']}");

                continue;
            }

            foreach ($province['cities'] as $city) {
                $model = City::query()->firstOrCreate([
                    'state_id' => $stateId,
                    'name' => ucwords(mb_strtolower($city['name'])),
                ], [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => 1,
                    'updated_by' => 1,
                ]);

                if ($model->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        $this->reportCreated($created);
    }
}
