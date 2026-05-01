<?php

declare(strict_types=1);

namespace Modules\Workflow\Approval;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Workflow\Contracts\Approvable;
use Modules\Workflow\Enums\ApprovalDecision;
use Modules\Workflow\Models\ApprovalRecord;

final class ApprovalStep
{
    private int $atLeast = 1;

    /** @var string[] */
    private array $roles = [];

    /** @var string[] */
    private array $permissions = [];

    private ?Closure $canApproveUsing = null;

    private function __construct(private string $name) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function role(string|array $roles): self
    {
        $this->roles = array_merge($this->roles, (array) $roles);

        return $this;
    }

    public function permission(string|array $permissions): self
    {
        $this->permissions = array_merge($this->permissions, (array) $permissions);

        return $this;
    }

    public function atLeast(int $count): self
    {
        $this->atLeast = $count;

        return $this;
    }

    public function canApproveUsing(Closure $callback): self
    {
        $this->canApproveUsing = $callback;

        return $this;
    }

    public function canApprove(Model $user, Approvable&Model $approvable): bool
    {
        if ($this->canApproveUsing !== null) {
            return (bool) app()->call($this->canApproveUsing, [
                'user' => $user,
                'approvable' => $approvable,
            ]);
        }

        if (! empty($this->roles) && method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($this->roles);
        }

        if (! empty($this->permissions) && method_exists($user, 'hasAnyPermission')) {
            return $user->hasAnyPermission($this->permissions);
        }

        return false;
    }

    public function evaluate(Approvable&Model $approvable, string $flowKey): ApprovalDecision
    {
        $records = $approvable->approvalRecords()
            ->where('flow_key', $flowKey)
            ->where('step', $this->name)
            ->get();

        return $this->evaluateWithRecords($records);
    }

    /**
     * Evaluates this step against a pre-loaded collection of approval records,
     * avoiding redundant queries when called from ApprovalFlow::evaluate().
     *
     * @param  Collection<int, ApprovalRecord>  $records
     */
    public function evaluateWithRecords(Collection $records): ApprovalDecision
    {
        if ($records->where('decision', ApprovalDecision::Rejected->value)->isNotEmpty()) {
            return ApprovalDecision::Rejected;
        }

        if ($records->where('decision', ApprovalDecision::Approved->value)->count() >= $this->atLeast) {
            return ApprovalDecision::Approved;
        }

        return ApprovalDecision::Open;
    }
}
