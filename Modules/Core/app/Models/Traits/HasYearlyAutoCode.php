<?php

declare(strict_types=1);

namespace Modules\Core\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Codes\YearlyCodeGenerator;

trait HasYearlyAutoCode
{
    /**
     * Boot the HasYearlyAutoCode trait for a model.
     * Generates codes in the format: PREFIX-YEAR-NNNNNN (e.g. COT-2026-000001)
     */
    public static function bootHasYearlyAutoCode(): void
    {
        static::creating(function (Model $model) {
            if (blank($model->code)) {
                $model->code = YearlyCodeGenerator::next(
                    modelClass: $model::class,
                    prefix: method_exists($model, 'getCodePrefix') ? $model::getCodePrefix() : mb_strtoupper(mb_substr(class_basename($model), 0, 3)),
                    year: now()->year,
                    padding: property_exists($model, 'codePadding') ? $model->codePadding : 6,
                    scope: method_exists($model, 'getCodeScope') ? $model->getCodeScope() : [],
                );
            }
        });
    }
}
