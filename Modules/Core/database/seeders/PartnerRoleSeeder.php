<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders;

use BackedEnum;
use Illuminate\Database\Seeder;
use Modules\Core\Database\Seeders\Concerns\ReportsSeederProgress;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\People\Models\PartnerRole;

final class PartnerRoleSeeder extends Seeder
{
    use ReportsSeederProgress;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $created = 0;
        $updated = 0;

        // Seed all enum cases dynamically
        $enumCases = PartnerRoleEnum::cases();
        $enumCodes = collect($enumCases)->map(fn ($case) => $case->value)->all();

        foreach ($enumCases as $case) {
            $model = PartnerRole::query()->updateOrCreate(
                ['code' => $case->value],
                [
                    'name' => $case->displayName(),
                    'description' => $case->description(),
                    'is_active' => true,
                ],
            );
            $this->tallyModelChange($model, $created, $updated);
        }

        // Optionally: Remove roles not present in enum (hard delete)
        $deleted = 0;
        $dbCodes = collect(PartnerRole::query()->pluck('code'))
            ->map(fn ($c) => $c instanceof BackedEnum ? $c->value : $c)
            ->all();

        $toDelete = array_diff($dbCodes, $enumCodes);
        if (! empty($toDelete)) {
            $deleted = PartnerRole::query()->whereIn('code', $toDelete)->delete();
        }

        $this->reportCreatedAndUpdated($created, $updated);
        if ($deleted > 0) {
            $this->command->warn('PartnerRoleSeeder: Deleted roles not present in enum: ['.implode(', ', $toDelete).']');
        }
    }
}
