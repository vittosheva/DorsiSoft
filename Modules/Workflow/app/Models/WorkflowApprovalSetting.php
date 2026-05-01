<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\Traits\HasTenancy;

final class WorkflowApprovalSetting extends Model
{
    use HasFactory;
    use HasTenancy;

    protected $table = 'workflow_approval_settings';

    protected $fillable = [
        'company_id',
        'flow_key',
        'is_enabled',
        'min_amount',
        'required_roles',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'min_amount' => 'decimal:2',
            'required_roles' => 'array',
        ];
    }

    /**
     * Checks if the approval flow is active for a given amount.
     * Returns true if enabled AND (no min_amount OR amount >= min_amount).
     */
    public function isActiveForAmount(?float $amount): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if ($this->min_amount !== null && ($amount ?? 0.0) < (float) $this->min_amount) {
            return false;
        }

        return true;
    }
}
