<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Support\Actions\DangerRecordStatusAction;
use Modules\Core\Support\Actions\DuplicateRecordAction;
use Modules\Core\Support\Actions\TransitionRecordStatusAction;
use Modules\Sales\Enums\DeliveryGuideStatusEnum;
use Modules\Sales\Models\DeliveryGuide;
use Modules\Sales\Services\DocumentIssuanceService;
use Modules\Sri\Exceptions\XmlGenerationException;

trait InteractsWithDeliveryGuideHeaderActions
{
    protected function getDeliveryGuideVoidAction(): Action
    {
        /** @var DeliveryGuide $record */
        $record = $this->getRecord();

        return DangerRecordStatusAction::make('void')
            ->iconButton()
            ->hiddenLabel()
            ->tooltip(__('Void Delivery Guide'))
            ->modalHeading(__('Void Delivery Guide'))
            ->schema([
                Textarea::make('voided_reason')
                    ->required()
                    ->maxLength(500),
            ])
            ->visible(fn () => $record->status === DeliveryGuideStatusEnum::Issued)
            ->applyTransitionUsing(function (DeliveryGuide $record, array $data): void {
                DB::transaction(function () use ($record, $data): void {
                    $record->voided_at = now();
                    $record->voided_reason = (string) ($data['voided_reason'] ?? '');
                    $record->status = DeliveryGuideStatusEnum::Voided;
                    $record->save();
                });
            })
            ->notificationTitleUsing(fn (): string => __('Delivery guide voided'));
    }

    protected function getDeliveryGuideIssueAction(): Action
    {
        /** @var DeliveryGuide $record */
        $record = $this->getRecord();

        return TransitionRecordStatusAction::make('issue')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(fn () => $record->status === DeliveryGuideStatusEnum::Draft)
            ->applyTransitionUsing(function (DeliveryGuide $record, DocumentIssuanceService $issuanceService): void {
                try {
                    $issuanceService->issueDeliveryGuide($record);
                } catch (InvalidArgumentException|XmlGenerationException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('Cannot issue delivery guide'))
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();

                    throw new Halt;
                }
            })
            ->notificationTitleUsing(fn (): string => __('Delivery guide issued'))
            ->redirectUrlUsing(fn (DeliveryGuide $record): string => $this->getResource()::getUrl('view', ['record' => $record]));
    }

    protected function getDeliveryGuideDuplicateAction(string $name = 'duplicate'): Action
    {
        return DuplicateRecordAction::make($name)
            ->exceptAttributes(['code', 'status', 'issue_date', 'voided_at', 'voided_reason', 'sequential_number', 'access_key'])
            ->mutateRecordUsing(function (DeliveryGuide $newDeliveryGuide): void {
                $newDeliveryGuide->status = DeliveryGuideStatusEnum::Draft;
            })
            ->successTitleUsing(fn (): string => __('Delivery Guide Duplicated'))
            ->redirectUrlUsing(fn (DeliveryGuide $newDeliveryGuide): string => $this->getResource()::getUrl('edit', ['record' => $newDeliveryGuide]));
    }
}
