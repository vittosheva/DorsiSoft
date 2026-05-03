<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Modules\Core\Support\Actions\DangerRecordStatusAction;
use Modules\Core\Support\Actions\DuplicateRecordAction;
use Modules\Core\Support\Actions\EditCompanyAction;
use Modules\Core\Support\Actions\TransitionRecordStatusAction;
use Modules\Sales\Enums\WithholdingStatusEnum;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\PurchaseSettlementResource;
use Modules\Sales\Models\Withholding;
use Modules\Sales\Services\DocumentIssuanceService;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Workflow\Enums\ApprovalFlowKey;
use Modules\Workflow\Filament\Actions\ApprovalAction;

trait InteractsWithWithholdingHeaderActions
{
    /**
     * @return array<int, Action>
     */
    protected function getWithholdingApprovalActions(): array
    {
        return [
            ApprovalAction::makeApprove()
                ->flowKey(ApprovalFlowKey::WithholdingRelease->value)
                ->stepName('accountant'),

            ApprovalAction::makeReject()
                ->flowKey(ApprovalFlowKey::WithholdingRelease->value)
                ->stepName('accountant'),

            ApprovalAction::makeReset()
                ->flowKey(ApprovalFlowKey::WithholdingRelease->value)
                ->stepName('accountant'),
        ];
    }

    protected function getWithholdingIssueAction(): Action
    {
        return TransitionRecordStatusAction::make('issue')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(function (): bool {
                /** @var Withholding $record */
                $record = $this->getRecord();

                $key = ApprovalFlowKey::WithholdingRelease->value;

                if ($record->isApprovalRequired($key) && ! $record->isApproved($key)) {
                    return false;
                }

                return $record->status === WithholdingStatusEnum::Draft;
            })
            ->applyTransitionUsing(function (Withholding $record, DocumentIssuanceService $issuanceService): void {
                try {
                    $issuanceService->issueWithholding($record, Auth::id());
                } catch (InvalidArgumentException|XmlGenerationException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('Cannot issue withholding'))
                        ->body($e->getMessage())
                        ->persistent()
                        ->actions([
                            // EditCompanyAction::make(),
                        ])
                        ->send();

                    throw new Halt;
                }
            })
            ->notificationTitleUsing(fn (): string => __('Withholding issued'))
            ->redirectUrlUsing(fn (Withholding $record): string => $this->getResource()::getUrl('view', ['record' => $record]));
    }

    protected function getWithholdingVoidAction(): Action
    {
        /** @var Withholding $record */
        $record = $this->getRecord();

        return DangerRecordStatusAction::make('void')
            ->modalHeading(__('Void Withholding'))
            ->schema([
                Textarea::make('voided_reason')
                    ->required()
                    ->maxLength(500),
            ])
            ->visible(fn () => $record->status === WithholdingStatusEnum::Issued)
            ->applyTransitionUsing(function (Withholding $record, array $data): void {
                $record->voided_at = now();
                $record->voided_reason = (string) ($data['voided_reason'] ?? '');
                $record->status = WithholdingStatusEnum::Voided;
                $record->save();
            })
            ->notificationTitleUsing(fn (): string => __('Withholding voided'));
    }

    protected function getViewSourceSettlementAction(): Action
    {
        return Action::make('view_source_settlement')
            ->icon(Heroicon::DocumentText)
            ->color('gray')
            ->visible(function (): bool {
                /** @var Withholding $record */
                $record = $this->getRecord();

                return filled($record->source_purchase_settlement_id);
            })
            ->url(function (): string {
                /** @var Withholding $record */
                $record = $this->getRecord();

                return PurchaseSettlementResource::getUrl('view', [
                    'record' => $record->source_purchase_settlement_id,
                ]);
            });
    }

    protected function getWithholdingDuplicateAction(string $name = 'duplicate'): Action
    {
        return DuplicateRecordAction::make($name)
            ->exceptAttributes([
                'code',
                'status',
                'issue_date',
                'voided_at',
                'voided_reason',
                'sequential_number',
                'access_key',
                'electronic_status',
                'electronic_submitted_at',
                'electronic_authorized_at',
                'correction_status',
                'correction_source_id',
                'superseded_by_id',
                'correction_requested_at',
                'corrected_at',
                'correction_reason',
            ])
            ->mutateRecordUsing(function (Withholding $newWithholding): void {
                $newWithholding->status = WithholdingStatusEnum::Draft;
            })
            ->successTitleUsing(fn (): string => __('Withholding Duplicated'))
            ->redirectUrlUsing(fn (Withholding $newWithholding): string => $this->getResource()::getUrl('edit', ['record' => $newWithholding]));
    }
}
