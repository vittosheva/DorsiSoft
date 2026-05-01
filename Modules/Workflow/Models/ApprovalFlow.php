<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\Traits\HasTenancy;

final class ApprovalFlow extends Model
{
    use HasTenancy;

    protected $table = 'approval_flows';

    protected $fillable = [
        'company_id',
        'key',
        'name',
        'document_type_id',
        'is_active',
        'min_amount',
        'version',
    ];

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ApprovalFlowRole::class);
    }
}
