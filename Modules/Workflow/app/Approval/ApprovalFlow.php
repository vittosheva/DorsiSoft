<?php

declare(strict_types=1);

namespace Modules\Workflow\Approval;

use Illuminate\Database\Eloquent\Model;
use Modules\Workflow\Contracts\Approvable;
use Modules\Workflow\Enums\ApprovalDecision;

final class ApprovalFlow
{
    /** @var ApprovalStep[] */
    private array $steps = [];

    private function __construct(private string $key) {}

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function step(ApprovalStep $step): self
    {
        $this->steps[] = $step;

        return $this;
    }

    /**
     * @return ApprovalStep[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    public function getStep(string $name): ?ApprovalStep
    {
        foreach ($this->steps as $step) {
            if ($step->getName() === $name) {
                return $step;
            }
        }

        return null;
    }

    public function evaluate(Approvable&Model $approvable): ApprovalDecision
    {
        if (empty($this->steps)) {
            return ApprovalDecision::Open;
        }

        // Load all approval records for this flow in a single query, grouped by step name.
        $allRecords = $approvable->approvalRecords()
            ->where('flow_key', $this->key)
            ->get()
            ->groupBy('step');

        foreach ($this->steps as $step) {
            $records = $allRecords->get($step->getName(), collect());
            if ($step->evaluateWithRecords($records) === ApprovalDecision::Rejected) {
                return ApprovalDecision::Rejected;
            }
        }

        foreach ($this->steps as $step) {
            $records = $allRecords->get($step->getName(), collect());
            $decision = $step->evaluateWithRecords($records);

            if ($decision !== ApprovalDecision::Approved) {
                return $records->isNotEmpty() ? ApprovalDecision::Pending : ApprovalDecision::Open;
            }
        }

        return ApprovalDecision::Approved;
    }
}
