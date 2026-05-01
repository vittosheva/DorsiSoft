<?php

declare(strict_types=1);

namespace Modules\Sri\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\People\Models\User;
use Modules\Sri\Enums\SriDocumentTypeEnum;

/**
 * Historial de cambios en secuenciales SRI.
 * NO usa TenantScope — el service filtra por company_id explícitamente.
 *
 * @property int $id
 * @property int $document_sequence_id
 * @property int $company_id
 * @property string $establishment_code
 * @property string $emission_point_code
 * @property SriDocumentTypeEnum $document_type
 * @property string $event
 * @property int $previous_value
 * @property int $new_value
 * @property string|null $reason
 * @property int|null $performed_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class DocumentSequenceHistory extends Model
{
    use HasTenancy;

    protected $table = 'sales_document_sequence_history';

    protected $fillable = [
        'document_sequence_id',
        'company_id',
        'establishment_code',
        'emission_point_code',
        'document_type',
        'event',
        'previous_value',
        'new_value',
        'reason',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => SriDocumentTypeEnum::class,
            'previous_value' => 'integer',
            'new_value' => 'integer',
            'performed_by' => 'integer',
        ];
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(DocumentSequence::class, 'document_sequence_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by')->withoutGlobalScopes();
    }
}
