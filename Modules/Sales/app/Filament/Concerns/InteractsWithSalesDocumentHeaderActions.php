<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Core\Support\Actions\RefreshSnapshotAction;
use Modules\Core\Support\Actions\SendDocumentEmailAction;
use Modules\Core\Support\Actions\SeparatorAction;
use Modules\Sri\Support\Actions\CorrectRejectedElectronicDocumentAction;
use Modules\Sri\Support\Actions\DownloadXmlAction;
use Modules\Sri\Support\Actions\GenerateXmlAction;
use Modules\Sri\Support\Actions\PollElectronicAuthorizationAction;
use Modules\Sri\Support\Actions\RetryElectronicAction;
use Modules\Sri\Support\Actions\SendAutomaticElectronicDocumentEmailAction;
use Modules\Sri\Support\Actions\ShowElectronicAuditAction;
use Modules\Sri\Support\Actions\ViewXmlAction;
use ReflectionFunction;
use Throwable;

trait InteractsWithSalesDocumentHeaderActions
{
    /**
     * @param  Action|ActionGroup  $action
     */
    protected static function getSalesDocumentActionVisibilityCondition(Action $action): bool|Closure
    {
        return Closure::bind(function (): bool|Closure {
            return $this->isVisible;
        }, $action, $action)();
    }

    /**
     * @param  Action|ActionGroup  $action
     */
    protected static function actionVisibilityDependsOnRuntimeContext(Action $action): bool
    {
        $visibility = self::getSalesDocumentActionVisibilityCondition($action);

        if (! $visibility instanceof Closure) {
            return false;
        }

        return (new ReflectionFunction($visibility))->getNumberOfParameters() > 0;
    }

    /**
     * @param  Action|ActionGroup|null  $action
     */
    protected static function isRenderableSalesDocumentAction($action): bool
    {
        if ($action instanceof ActionGroup) {
            // Considerar renderizable si tiene al menos una acción renderizable
            foreach ($action->getActions() as $subAction) {
                if (self::isRenderableSalesDocumentAction($subAction)) {
                    return true;
                }
            }

            return false;
        }
        if (! $action instanceof Action) {
            return false;
        }
        if (self::actionVisibilityDependsOnRuntimeContext($action)) {
            return true;
        }
        try {
            return $action->isVisible();
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * Internal helper that assembles visible sections with separators.
     *
     * @param  array<int, Action|ActionGroup>  ...$sections
     * @return array<int, Action|ActionGroup>
     */
    protected function composeSalesDocumentActionSections(array ...$sections): array
    {
        $groups = [];

        foreach ($sections as $actions) {
            $actions = array_values(array_filter(
                $actions,
                static fn ($action): bool => self::isRenderableSalesDocumentAction($action),
            ));

            if ($actions === []) {
                continue;
            }

            if ($groups !== []) {
                $groups[] = SeparatorAction::make();
            }

            array_push($groups, ...$actions);
        }

        return $groups;
    }

    /**
     * @param  array<int, Action|ActionGroup>  $approvalActions
     * @param  array<int, Action|ActionGroup>  $primaryActions
     * @param  array<int, Action|ActionGroup>  $electronicActions
     * @param  array<int, Action|ActionGroup>  $managementActions
     * @return array<int, Action|ActionGroup>
     */
    protected function composeSalesDocumentHeaderActions(
        array $approvalActions = [],
        array $primaryActions = [],
        array $electronicActions = [],
        array $managementActions = [],
    ): array {
        return $this->composeSalesDocumentActionSections(
            $approvalActions,
            $primaryActions,
            $electronicActions,
            $managementActions,
        );
    }

    /**
     * @param  array<int, Action>  $extraActions
     * @return array<int, Action>
     */
    protected function getSalesDocumentPrimaryActions(
        bool $includeEmail = true,
        bool $includePdf = true,
        array $extraActions = [],
    ): array {
        $actions = [];

        if ($includeEmail) {
            $actions[] = ActionGroup::make([
                SendAutomaticElectronicDocumentEmailAction::make(),
                SendDocumentEmailAction::make(),
            ])
                ->label(__('Email'))
                ->icon(Heroicon::Envelope)
                ->color('info')
                ->button();
            // $actions[] = SendDocumentEmailAction::make();
        }

        if ($includePdf) {
            $actions[] = GeneratePdfAction::make();
        }

        $actions[] = RefreshSnapshotAction::make();

        array_push($actions, ...$extraActions);

        return $actions;
    }

    /**
     * @return array<int, Action>
     */
    protected function getSalesDocumentElectronicActions(string $resourceClass): array
    {
        return [
            GenerateXmlAction::make(),
            ActionGroup::make([
                ViewXmlAction::make(),
                DownloadXmlAction::make(),
            ])
                ->label(__('XML'))
                ->icon(Heroicon::CodeBracket)
                ->color('primary')
                ->button(),
            PollElectronicAuthorizationAction::make(),
            CorrectRejectedElectronicDocumentAction::make()->resource($resourceClass),
            RetryElectronicAction::make(),
            ShowElectronicAuditAction::make(),
        ];
    }

    /**
     * @param  array<int, Action>  $approvalActions
     * @param  array<int, Action>  $primaryActions
     * @param  array<int, Action>  $electronicActions
     * @param  array<int, Action>  $managementExtraActions
     * @return array<int, Action>
     */
    protected function getSalesElectronicDocumentViewHeaderActions(
        string $resourceClass,
        array $approvalActions = [],
        array $primaryActions = [],
        array $electronicActions = [],
        ?Action $duplicateAction = null,
        array $managementExtraActions = [],
    ): array {
        return $this->composeSalesDocumentHeaderActions(
            approvalActions: $approvalActions,
            primaryActions: $primaryActions,
            electronicActions: [
                ...$electronicActions,
                ...$this->getSalesDocumentElectronicActions($resourceClass),
            ],
            managementActions: $this->getSalesDocumentManagementActions($duplicateAction, $managementExtraActions),
        );
    }

    /**
     * @param  array<int, Action>  $primaryActions
     * @param  array<int, Action>  $electronicActions
     * @param  array<int, Action>  $managementExtraActions
     * @return array<int, Action>
     */
    protected function getSalesElectronicDocumentEditHeaderActions(
        string $resourceClass,
        array $primaryActions = [],
        array $electronicActions = [],
        ?Action $duplicateAction = null,
        ?Action $deleteAction = null,
        array $managementExtraActions = [],
        bool $includeCreate = true,
        bool $includeView = true,
    ): array {
        return $this->composeSalesDocumentHeaderActions(
            primaryActions: $primaryActions,
            electronicActions: [
                ...$electronicActions,
                ...$this->getSalesDocumentElectronicActions($resourceClass),
            ],
            managementActions: $this->getSalesDocumentEditManagementActions(
                $duplicateAction,
                $deleteAction,
                $managementExtraActions,
                $includeCreate,
                $includeView,
            ),
        );
    }

    /**
     * @param  array<int, Action>  $extraActions
     * @return array<int, Action>
     */
    protected function getSalesDocumentManagementActions(?Action $duplicateAction = null, array $extraActions = []): array
    {
        $actions = [
            CreateAction::make(),
            EditAction::make(),
        ];

        if ($duplicateAction !== null) {
            $actions[] = $duplicateAction;
        }

        array_push($actions, ...$extraActions);

        return $actions;
    }

    /**
     * @param  array<int, Action>  $extraActions
     * @return array<int, Action>
     */
    protected function getSalesDocumentEditManagementActions(
        ?Action $duplicateAction = null,
        ?Action $deleteAction = null,
        array $extraActions = [],
        bool $includeCreate = true,
        bool $includeView = true,
    ): array {
        $actions = [];

        if ($includeCreate) {
            $actions[] = CreateAction::make();
        }

        if ($includeView) {
            $actions[] = ViewAction::make();
        }

        if ($duplicateAction !== null) {
            $actions[] = $duplicateAction;
        }

        if ($deleteAction !== null) {
            $actions[] = $deleteAction;
        }

        if ($deleteAction === null && $includeCreate && $includeView) {
            $actions[] = DeleteAction::make();
        }

        array_push($actions, ...$extraActions);

        return $actions;
    }
}
