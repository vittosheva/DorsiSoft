<?php

declare(strict_types=1);

namespace Modules\Workflow\Filament\CoreApp\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Workflow\Contracts\Approvable;
use Modules\Workflow\Models\ApprovalRecord;

final class ApprovalHistoryWidget extends Widget
{
    public ?Model $record = null;

    protected string $view = 'workflow::widgets.approval-history';

    /**
     * @return Collection<int, ApprovalRecord>
     */
    public function getApprovalRecords(): Collection
    {
        if ($this->record === null || ! $this->record instanceof Approvable) {
            return new Collection;
        }

        return $this->record
            ->approvalRecords()
            ->with(['approver:id,name'])
            ->withTrashed()
            ->orderByDesc('decided_at')
            ->get();
    }

    public function shouldShowWidget(): bool
    {
        return $this->record instanceof Approvable;
    }
}
