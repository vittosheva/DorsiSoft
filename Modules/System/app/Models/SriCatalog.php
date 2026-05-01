<?php

declare(strict_types=1);

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Support\Models\BaseModel;
use Modules\Sri\Database\Factories\SriCatalogFactory;
use Modules\Sri\Enums\SriCatalogTypeEnum;

final class SriCatalog extends BaseModel
{
    use HasFactory;

    protected $table = 'sri_catalogs';

    protected $fillable = [
        'catalog_type',
        'code',
        'name',
        'description',
        'extra_data',
        'is_active',
        'valid_from',
        'valid_to',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'catalog_type' => SriCatalogTypeEnum::class,
            'extra_data' => 'array',
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Returns active catalog entries for the given type, ordered and cached.
     *
     * @return Collection<int, self>
     */
    public static function forType(SriCatalogTypeEnum $type): Collection
    {
        $key = 'sri_catalogs.'.$type->value;

        return Cache::remember(
            $key,
            3600,
            fn (): Collection => self::query()
                ->active()
                ->ofType($type)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get()
        );
    }

    /**
     * @return array<string, string>
     */
    public static function optionsForType(SriCatalogTypeEnum $type): array
    {
        return self::forType($type)
            ->mapWithKeys(fn (self $catalog): array => [$catalog->code => $catalog->code.' — '.$catalog->name])
            ->all();
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    public function ofType(Builder $query, SriCatalogTypeEnum|string $type): void
    {
        $query->where('catalog_type', $type instanceof SriCatalogTypeEnum ? $type->value : $type);
    }

    #[Scope]
    public function validAt(Builder $query, Carbon $date): void
    {
        $query->where(
            fn (Builder $q): Builder => $q
                ->whereNull('valid_from')
                ->orWhere('valid_from', '<=', $date)
        )->where(
            fn (Builder $q): Builder => $q
                ->whereNull('valid_to')
                ->orWhere('valid_to', '>=', $date)
        );
    }

    protected static function newFactory(): Factory
    {
        return SriCatalogFactory::new();
    }
}
