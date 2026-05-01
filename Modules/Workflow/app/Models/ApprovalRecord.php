<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\People\Models\User;
use Modules\Workflow\Enums\ApprovalDecision;

final class ApprovalRecord extends Model
{
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;

    protected $table = 'workflow_approvals';

    protected $fillable = [
        'company_id',
        'flow_key',
        'step',
        'approvable_id',
        'approvable_type',
        'approver_id',
        'decision',
        'notes',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decision' => ApprovalDecision::class,
            'decided_at' => 'datetime',
        ];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id')->withoutGlobalScopes();
    }
}
