<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Inventory\Enums\MovementTypeEnum;

final class InventoryDocumentType extends Model
{
    protected $table = 'inv_document_types';

    protected $fillable = [
        'code',
        'name',
        'movement_type',
        'affects_inventory',
        'requires_source_document',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => MovementTypeEnum::class,
            'affects_inventory' => 'boolean',
            'requires_source_document' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'document_type_id');
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    public function affectsInventory(Builder $query): void
    {
        $query->where('affects_inventory', true);
    }
}
