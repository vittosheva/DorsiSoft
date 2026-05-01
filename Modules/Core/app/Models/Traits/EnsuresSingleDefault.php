<?php

declare(strict_types=1);

namespace Modules\Core\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait EnsuresSingleDefault
{
    /**
     * Register the listener that keeps one record flagged as default per scope.
     */
    protected static function bootEnsuresSingleDefault(): void
    {
        static::saving(function (Model $model): void {
            $attribute = static::singleDefaultAttribute();

            if (! $model->isDirty($attribute)) {
                return;
            }

            if (! $model->getAttribute($attribute)) {
                return;
            }

            if ($activationAttribute = static::singleDefaultActivationAttribute()) {
                if (! $model->getAttribute($activationAttribute)) {
                    $model->setAttribute($activationAttribute, true);
                }
            }

            static::resetSingleDefaultFlags($model);
        });
    }

    /**
     * Get the attribute that should remain unique per scope.
     */
    protected static function singleDefaultAttribute(): string
    {
        return 'is_default';
    }

    /**
     * Optionally activate another attribute when assigning the default flag.
     */
    protected static function singleDefaultActivationAttribute(): ?string
    {
        return null;
    }

    /**
     * Scope the reset query so only related records drop their default flag.
     */
    protected static function singleDefaultScopeColumn(): ?string
    {
        return null;
    }

    /**
     * Clear the default flag for scoped records before saving the new default.
     */
    protected static function resetSingleDefaultFlags(Model $model): void
    {
        $attribute = static::singleDefaultAttribute();

        $query = $model::query()
            ->where($attribute, true);

        if ($model->exists) {
            $query->whereKeyNot($model->getKey());
        }

        static::applySingleDefaultScope($model, $query);

        $query->update([$attribute => false]);
    }

    /**
     * Apply any scope filters to the single-default query.
     */
    protected static function applySingleDefaultScope(Model $model, Builder $query): void
    {
        $scopeColumn = static::singleDefaultScopeColumn();

        if ($scopeColumn === null) {
            return;
        }

        $value = $model->getAttribute($scopeColumn);

        if ($value === null) {
            return;
        }

        $query->where($scopeColumn, $value);
    }
}
