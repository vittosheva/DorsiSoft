<?php

declare(strict_types=1);

namespace Modules\Sri\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\People\Models\User;

final class SriElectronicEvent extends Model
{
    use HasTenancy;

    protected $table = 'sri_electronic_events';

    protected $fillable = [
        'company_id',
        'documentable_type',
        'documentable_id',
        'event',
        'status_from',
        'status_to',
        'triggered_by',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
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
