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
use Modules\Core\Support\Actions\EditCompanyAction;
use Modules\Core\Support\Actions\TransitionRecordStatusAction;
use Modules\Sales\Enums\DebitNoteStatusEnum;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Services\DocumentIssuanceService;
use Modules\Sri\Exceptions\XmlGenerationException;

trait InteractsWithDebitNoteHeaderActions
{
    protected function getDebitNoteVoidAction(): Action
    {
        /** @var DebitNote $record */
        $record = $this->getRecord();

        return DangerRecordStatusAction::make('void')
            ->modalHeading(__('Void Debit Note'))
            ->schema([
                Textarea::make('voided_reason')
                    ->required()
                    ->maxLength(500),
            ])
            ->visible(fn () => $record->status === DebitNoteStatusEnum::Issued)
            ->applyTransitionUsing(function (DebitNote $record, array $data): void {
                DB::transaction(function () use ($record, $data): void {
                    $record->voided_at = now();
                    $record->voided_reason = (string) ($data['voided_reason'] ?? '');
                    $record->status = DebitNoteStatusEnum::Voided;
                    $record->save();
                });
            })
            ->notificationTitleUsing(fn (): string => __('Debit note voided'));
    }

    protected function getDebitNoteIssueAction(): Action
    {
        /** @var DebitNote $record */
        $record = $this->getRecord();

        return TransitionRecordStatusAction::make('issue')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(fn () => $record->status === DebitNoteStatusEnum::Draft)
            ->applyTransitionUsing(function (DebitNote $record, DocumentIssuanceService $issuanceService): void {
                try {
                    $issuanceService->issueDebitNote($record);
                } catch (InvalidArgumentException|XmlGenerationException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('Cannot issue debit note'))
                        ->body($e->getMessage())
                        ->persistent()
                        ->actions([
                            // EditCompanyAction::make(),
                        ])
                        ->send();

                    throw new Halt;
                }
            })
            ->notificationTitleUsing(fn (): string => __('Debit note issued'))
            ->redirectUrlUsing(fn (DebitNote $record): string => $this->getResource()::getUrl('view', ['record' => $record]));
    }

    protected function getDebitNoteDuplicateAction(string $name = 'duplicate'): Action
    {
        return DuplicateRecordAction::make($name)
            ->exceptAttributes(['code', 'status', 'issue_date', 'voided_at', 'voided_reason'])
            ->mutateRecordUsing(function (DebitNote $newDebitNote): void {
                $newDebitNote->status = DebitNoteStatusEnum::Draft;
            })
            ->successTitleUsing(fn (): string => __('Debit Note Duplicated'))
            ->redirectUrlUsing(fn (DebitNote $newDebitNote): string => $this->getResource()::getUrl('edit', ['record' => $newDebitNote]));
    }
}
