<?php

declare(strict_types=1);

namespace Modules\Core\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Core\Support\Codes\CodeGenerator;

trait HasAutoCode
{
    /**
     * Boot the HasAutoCode trait for a model.
     */
    public static function bootHasAutoCode(): void
    {
        static::creating(function (Model $model) {
            if (blank($model->code)) {
                $model->code = CodeGenerator::next(
                    modelClass: $model::class,
                    prefix: method_exists($model, 'getCodePrefix') ? $model::getCodePrefix() : static::defaultCodePrefix($model),
                    padding: property_exists($model, 'codePadding') ? $model->codePadding : 3,
                    scope: method_exists($model, 'getCodeScope') ? $model->getCodeScope() : [],
                );
            }
        });
    }

    /**
     * Default code prefix if getCodePrefix() is not defined.
     */
    protected static function defaultCodePrefix(Model $model): string
    {
        // Use first 3 uppercase letters of the class name as default prefix
        return mb_strtoupper(Str::substr(class_basename($model), 0, 3));
    }
}
