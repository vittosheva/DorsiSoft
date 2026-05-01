<?php

declare(strict_types=1);

namespace Modules\Workflow\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Modules\Workflow\Approval\ApprovalFlow;
use Modules\Workflow\Enums\ApprovalDecision;
use Modules\Workflow\Events\ApprovalRequested;
use Modules\Workflow\Models\ApprovalRecord;
use Modules\Workflow\Models\WorkflowApprovalSetting;

/**
 * Adds approval capabilities to any Eloquent model.
 *
 * Usage:
 *   1. Add `use HasApprovals;` to the model
 *   2. Implement `getApprovalFlows(): array` returning keyed ApprovalFlow instances
 *   3. Implement `Modules\Workflow\Contracts\Approvable` interface
 *
 * @mixin Model
 */
trait HasApprovals
{
    /**
     * After creation, dispatch ApprovalRequested for every flow that is active
     * for this tenant and document amount, so approvers are notified immediately.
     */
    public static function bootHasApprovals(): void
    {
        static::created(function (self $model): void {
            $amount = $model->total ?? $model->amount ?? null;

            foreach ($model->getApprovalFlows() as $flowKey => $flow) {
                $setting = WorkflowApprovalSetting::withoutGlobalScopes()
                    ->where('company_id', $model->company_id)
                    ->where('flow_key', $flowKey)
                    ->where('is_enabled', true)
                    ->first();

                if (! $setting?->isActiveForAmount($amount !== null ? (float) $amount : null)) {
                    continue;
                }

                $firstStep = $flow->getSteps()[0] ?? null;

                if ($firstStep !== null) {
                    ApprovalRequested::dispatch($model, $flowKey, $firstStep->getName());
                }
            }
        });
    }

    /**
     * @return MorphMany<ApprovalRecord, $this>
     */
    public function approvalRecords(): MorphMany
    {
        return $this->morphMany(ApprovalRecord::class, 'approvable');
    }

    /**
     * Resolve the ApprovalFlow for a given key (defined in implementing model).
     */
    public function getApprovalFlow(string $key): ?ApprovalFlow
    {
        return $this->getApprovalFlows()[$key] ?? null;
    }

    /**
     * Compute the overall ApprovalDecision for a flow key.
     *
     * Checks the tenant setting first:
     * - If no setting exists or it's disabled → Open (no approval required)
     * - If a min_amount exists and this document is below it → Open
     * - Otherwise → delegates to the flow's evaluate() logic
     */
    public function approvalDecision(string $key): ApprovalDecision
    {
        /** @var WorkflowApprovalSetting|null $setting */
        $setting = $this->getCachedApprovalSetting($key);

        $rawAmount = $this->total ?? $this->amount ?? null;

        if ($setting === null || ! $setting->isActiveForAmount($rawAmount !== null ? (float) $rawAmount : null)) {
            return ApprovalDecision::Open;
        }

        return $this->getApprovalFlow($key)?->evaluate($this) ?? ApprovalDecision::Open;
    }

    /**
     * Returns true when a workflow setting is active for this document's amount.
     * Use this to distinguish "no approval needed" (Open + not required)
     * from "approval pending" (Open + required but no decision yet).
     */
    public function isApprovalRequired(string $key): bool
    {
        $setting = $this->getCachedApprovalSetting($key);
        $rawAmount = $this->total ?? $this->amount ?? null;

        return $setting !== null && $setting->isActiveForAmount($rawAmount !== null ? (float) $rawAmount : null);
    }

    public function isApproved(string $key): bool
    {
        return $this->approvalDecision($key) === ApprovalDecision::Approved;
    }

    public function isRejected(string $key): bool
    {
        return $this->approvalDecision($key) === ApprovalDecision::Rejected;
    }

    /**
     * Determines if a given user can take approval action on a specific step.
     *
     * If the tenant's WorkflowApprovalSetting defines required_roles, those roles
     * act as an override — the user must have at least one of them, regardless of
     * the roles hard-coded in the ApprovalStep definition.
     */
    public function canUserApprove(Model $user, string $key, string $stepName): bool
    {
        $flow = $this->getApprovalFlow($key);
        $step = $flow?->getStep($stepName);

        if ($step === null) {
            return false;
        }

        $overrideRoles = $this->getCachedApprovalSetting($key)?->required_roles;

        if (! empty($overrideRoles) && method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($overrideRoles);
        }

        return $step->canApprove($user, $this);
    }

    /**
     * Records an approval decision for a user on a specific step.
     * Wrapped in a transaction to prevent partial writes on concurrent approvals.
     */
    public function recordApproval(
        Model $approver,
        string $flowKey,
        string $stepName,
        ApprovalDecision $decision,
        ?string $notes = null
    ): ApprovalRecord {
        return DB::transaction(function () use ($approver, $flowKey, $stepName, $decision, $notes): ApprovalRecord {
            return $this->approvalRecords()->create([
                'company_id' => $this->company_id,
                'flow_key' => $flowKey,
                'step' => $stepName,
                'approver_id' => $approver->getKey(),
                'decision' => $decision,
                'notes' => $notes,
                'decided_at' => now(),
            ]);
        });
    }

    /**
     * Soft-deletes the active approval record for a user on a specific step (reset).
     */
    public function resetApproval(Model $approver, string $flowKey, string $stepName): bool
    {
        return (bool) $this->approvalRecords()
            ->where('flow_key', $flowKey)
            ->where('step', $stepName)
            ->where('approver_id', $approver->getKey())
            ->delete();
    }

    /**
     * Returns the WorkflowApprovalSetting for a given flow key, cached per-instance and per-key
     * to avoid redundant queries when the same document evaluates multiple flows.
     */
    private function getCachedApprovalSetting(string $key): ?WorkflowApprovalSetting
    {
        /** @var array<string, WorkflowApprovalSetting|null> $cache */
        static $cache = [];

        $cacheKey = "{$this->company_id}:{$key}";

        if (! array_key_exists($cacheKey, $cache)) {
            $cache[$cacheKey] = WorkflowApprovalSetting::where('company_id', $this->company_id)
                ->where('flow_key', $key)
                ->first();
        }

        return $cache[$cacheKey];
    }
}
