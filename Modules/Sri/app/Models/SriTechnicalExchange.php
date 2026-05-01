<?php

declare(strict_types=1);

namespace Modules\Sri\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\People\Models\User;

final class SriTechnicalExchange extends Model
{
    use HasTenancy;

    protected $table = 'sri_technical_exchanges';

    protected $fillable = [
        'company_id',
        'documentable_type',
        'documentable_id',
        'service',
        'operation',
        'environment',
        'endpoint',
        'status',
        'attempt',
        'request_summary',
        'response_summary',
        'request_body',
        'response_body',
        'error_class',
        'error_message',
        'duration_ms',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'request_summary' => 'array',
            'response_summary' => 'array',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by')->withoutGlobalScopes();
    }
}
