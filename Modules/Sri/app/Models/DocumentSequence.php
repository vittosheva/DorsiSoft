<?php

declare(strict_types=1);

namespace Modules\Sri\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Sri\Enums\SriDocumentTypeEnum;

/**
 * Tabla de control de secuenciales SRI por (empresa, establecimiento, punto, tipo).
 * NO usa TenantScope — el service filtra por company_id explícitamente.
 */
final class DocumentSequence extends Model
{
    use HasTenancy;

    protected $table = 'sales_document_sequences';

    protected $fillable = [
        'company_id',
        'establishment_code',
        'emission_point_code',
        'document_type',
        'last_sequential',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => SriDocumentTypeEnum::class,
            'last_sequential' => 'integer',
        ];
    }

    public function history(): HasMany
    {
        return $this->hasMany(DocumentSequenceHistory::class, 'document_sequence_id');
    }

    public function latestHistory(): HasOne
    {
        return $this->hasOne(DocumentSequenceHistory::class, 'document_sequence_id')
            ->ofMany([
                'created_at' => 'max',
                'id' => 'max',
            ]);
    }
}
