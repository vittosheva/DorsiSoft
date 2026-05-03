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
use Modules\Sales\Enums\PurchaseSettlementStatusEnum;
use Modules\Sales\Filament\CoreApp\Resources\PurchaseSettlements\PurchaseSettlementResource;
use Modules\Sales\Models\PurchaseSettlement;
use Modules\Sales\Services\DocumentIssuanceService;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Workflow\Enums\ApprovalFlowKey;
use Modules\Workflow\Filament\Actions\ApprovalAction;

trait InteractsWithPurchaseSettlementHeaderActions
{
    /**
     * @return array<int, Action>
     */
    protected function getPurchaseSettlementApprovalActions(): array
    {
        return [
            ApprovalAction::makeApprove()
                ->flowKey(ApprovalFlowKey::SettlementApproval->value)
                ->stepName('finance_director'),

            ApprovalAction::makeReject()
                ->flowKey(ApprovalFlowKey::SettlementApproval->value)
                ->stepName('finance_director'),

            ApprovalAction::makeReset()
                ->flowKey(ApprovalFlowKey::SettlementApproval->value)
                ->stepName('finance_director'),
        ];
    }

    protected function getPurchaseSettlementIssueAction(): Action
    {
        return TransitionRecordStatusAction::make('issue')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(function (): bool {
                /** @var PurchaseSettlement $record */
                $record = $this->getRecord();

                $key = ApprovalFlowKey::SettlementApproval->value;

                if ($record->isApprovalRequired($key) && ! $record->isApproved($key)) {
                    return false;
                }

                return $record->status === PurchaseSettlementStatusEnum::Draft;
            })
            ->applyTransitionUsing(function (PurchaseSettlement $record, DocumentIssuanceService $issuanceService): void {
                try {
                    $issuanceService->issuePurchaseSettlement($record, Auth::id());
                } catch (InvalidArgumentException|XmlGenerationException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('Cannot issue purchase settlement'))
                        ->body($e->getMessage())
                        ->persistent()
                        ->actions([
                            EditCompanyAction::make(),
                        ])
                        ->send();

                    throw new Halt;
                }
            })
            ->notificationTitleUsing(fn (): string => __('Purchase settlement issued'))
            ->redirectUrlUsing(fn (PurchaseSettlement $record): string => PurchaseSettlementResource::getUrl('view', ['record' => $record]));
    }

    protected function getPurchaseSettlementVoidAction(): Action
    {
        /** @var PurchaseSettlement $record */
        $record = $this->getRecord();

        return DangerRecordStatusAction::make('void')
            ->modalHeading(__('Void Purchase Settlement'))
            ->schema([
                Textarea::make('voided_reason')
                    ->required()
                    ->maxLength(500),
            ])
            ->visible(fn () => $record->status === PurchaseSettlementStatusEnum::Issued)
            ->applyTransitionUsing(function (PurchaseSettlement $record, array $data): void {
                $record->voided_at = now();
                $record->voided_reason = (string) ($data['voided_reason'] ?? '');
                $record->status = PurchaseSettlementStatusEnum::Voided;
                $record->save();
            })
            ->notificationTitleUsing(fn (): string => __('Purchase settlement voided'));
    }

    protected function getPurchaseSettlementDuplicateAction(string $name = 'duplicate'): Action
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
            ->mutateRecordUsing(function (PurchaseSettlement $newSettlement): void {
                $newSettlement->status = PurchaseSettlementStatusEnum::Draft;
            })
            ->successTitleUsing(fn (): string => __('Purchase Settlement Duplicated'))
            ->redirectUrlUsing(fn (PurchaseSettlement $newSettlement): string => PurchaseSettlementResource::getUrl('edit', ['record' => $newSettlement]));
    }
}
