<?php

declare(strict_types=1);

namespace Modules\Core\Support\Audit;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class AuditDisplayDataResolver
{
    public function resolve(?Model $record): array
    {
        return [
            'creator_name' => $this->resolveUserName($record, ['creator', 'createdBy']),
            'created_at' => $this->formatDate($record?->getAttribute('created_at')),
            'editor_name' => $this->resolveUserName($record, ['editor', 'updatedBy']),
            'updated_at' => $this->formatDate($record?->getAttribute('updated_at')),
        ];
    }

    public function resolveCreatorName(?Model $record): string
    {
        return $this->resolve($record)['creator_name'];
    }

    public function resolveCreatedAt(?Model $record): string
    {
        return $this->resolve($record)['created_at'];
    }

    public function resolveEditorName(?Model $record): string
    {
        return $this->resolve($record)['editor_name'];
    }

    public function resolveUpdatedAt(?Model $record): string
    {
        return $this->resolve($record)['updated_at'];
    }

    /**
     * @param  array<int, string>  $relations
     */
    private function resolveUserName(?Model $record, array $relations): string
    {
        if (! $record) {
            return '—';
        }

        foreach ($relations as $relation) {
            if (! $record->relationLoaded($relation) && ! method_exists($record, $relation)) {
                continue;
            }

            $related = $record->getRelationValue($relation);

            if (! $related instanceof Model) {
                continue;
            }

            $name = $related->getAttribute('name')
                ?? $related->getAttribute('full_name')
                ?? $related->getAttribute($related->getKeyName());

            if (filled($name)) {
                return (string) $name;
            }
        }

        return '—';
    }

    private function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        $date = $value instanceof CarbonInterface
            ? $value
            : Carbon::parse($value);

        return $date
            ->timezone((string) config('app.timezone'))
            ->translatedFormat('d/m/Y H:i');
    }
}
