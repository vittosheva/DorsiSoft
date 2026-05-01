<?php

declare(strict_types=1);

namespace Modules\System\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\System\Models\DocumentSeries;

/**
 * @extends Factory<DocumentSeries>
 */
final class DocumentSeriesFactory extends Factory
{
    protected $model = DocumentSeries::class;

    public function definition(): array
    {
        return [
            'establishment_id' => null,
            'prefix' => mb_strtoupper(fake()->lexify('???')),
            'current_sequence' => 0,
            'padding' => 6,
            'reset_year' => null,
            'auto_reset_yearly' => false,
            'is_active' => true,
        ];
    }
}
