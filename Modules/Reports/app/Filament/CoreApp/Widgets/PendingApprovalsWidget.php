<?php

declare(strict_types=1);

namespace Modules\Reports\Filament\CoreApp\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Workflow\Approval\ApprovalRegistry;
use Modules\Workflow\Contracts\Approvable;
use Modules\Workflow\Models\WorkflowApprovalSetting;

final class PendingApprovalsWidget extends Widget
{
    protected static ?int $sort = 7;

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected string $view = 'workflow::widgets.pending-approvals';

    /**
     * @return array<int, array{label: string, count: int, flow_key: string, step: string}>
     */
    public function getPendingItems(): array
    {
        /** @var Model|null $user */
        $user = Auth::user();
        $tenant = filament()->getTenant();

        if ($user === null || $tenant === null || ! method_exists($user, 'getRoleNames')) {
            return [];
        }

        /** @var array<int, string> $userRoles */
        $userRoles = $user->getRoleNames()->all();

        if (empty($userRoles)) {
            return [];
        }

        $flowModels = ApprovalRegistry::flowModels();
        $items = [];

        $enabledSettings = WorkflowApprovalSetting::query()
            ->where('company_id', $tenant->id)
            ->where('is_enabled', true)
            ->get();

        foreach ($enabledSettings as $setting) {
            $flowKey = $setting->flow_key;
            $modelClass = $flowModels[$flowKey] ?? null;

            if ($modelClass === null) {
                continue;
            }

            /** @var Approvable&Model $instance */
            $instance = new $modelClass;
            $flow = $instance->getApprovalFlows()[$flowKey] ?? null;

            if ($flow === null) {
                continue;
            }

            foreach ($flow->getSteps() as $step) {
                $stepRoles = ! empty($setting->required_roles)
                    ? $setting->required_roles
                    : $step->getRoles();

                if (empty(array_intersect($userRoles, $stepRoles))) {
                    continue;
                }

                $count = $modelClass::query()
                    ->where('company_id', $tenant->id)
                    ->whereDoesntHave(
                        'approvalRecords',
                        fn ($q) => $q
                            ->where('flow_key', $flowKey)
                            ->where('step', $step->getName())
                    )
                    ->count();

                if ($count > 0) {
                    $items[] = [
                        'label' => __('Flow: :flow — Step: :step', [
                            'flow' => $flowKey,
                            'step' => $step->getName(),
                        ]),
                        'count' => $count,
                        'flow_key' => $flowKey,
                        'step' => $step->getName(),
                    ];
                }

                break; // only the first applicable step per flow
            }
        }

        return $items;
    }

    public function shouldShowWidget(): bool
    {
        return ! empty($this->getPendingItems());
    }
}
