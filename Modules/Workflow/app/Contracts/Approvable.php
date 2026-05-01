<?php

declare(strict_types=1);

namespace Modules\Workflow\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Workflow\Approval\ApprovalFlow;
use Modules\Workflow\Enums\ApprovalDecision;
use Modules\Workflow\Models\ApprovalRecord;

interface Approvable
{
    /**
     * @return MorphMany<ApprovalRecord, $this>
     */
    public function approvalRecords(): MorphMany;

    /**
     * Returns all configured approval flows keyed by flow key.
     *
     * @return array<string, ApprovalFlow>
     */
    public function getApprovalFlows(): array;

    public function getApprovalFlow(string $key): ?ApprovalFlow;

    public function approvalDecision(string $key): ApprovalDecision;

    public function isApproved(string $key): bool;

    public function isRejected(string $key): bool;
}
