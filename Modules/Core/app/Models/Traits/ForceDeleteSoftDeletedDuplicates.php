<?php

declare(strict_types=1);

namespace Modules\Core\Models\Traits;

use Illuminate\Database\Eloquent\Model;

trait ForceDeleteSoftDeletedDuplicates
{
    public static function bootForceDeleteSoftDeletedDuplicates(): void
    {
        static::creating(function (Model $model): void {
            $columns = static::getUniqueConstraintColumns();

            $query = static::withTrashed();
            foreach ($columns as $column) {
                $query->where($column, $model->{$column});
            }

            $existing = $query->first();
            if ($existing && $existing->trashed()) {
                $existing->forceDelete();
            }
        });
    }

    /**
     * Columns that form the unique constraint used to detect soft-deleted duplicates.
     * Override in the model when the constraint differs.
     *
     * @return list<string>
     */
    public static function getUniqueConstraintColumns(): array
    {
        return [];
    }
}
