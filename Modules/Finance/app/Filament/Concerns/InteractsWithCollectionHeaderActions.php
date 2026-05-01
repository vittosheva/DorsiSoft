<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Modules\Core\Support\Actions\DangerRecordStatusAction;
use Modules\Core\Support\Actions\DuplicateRecordAction;
use Modules\Finance\Events\CollectionVoided;
use Modules\Finance\Models\Collection;
use Modules\Workflow\Filament\Actions\ApprovalAction;

trait InteractsWithCollectionHeaderActions
{
    /**
     * @return array<int, Action>
     */
    protected function getCollectionApprovalActions(): array
    {
        return [
            ApprovalAction::makeApprove()
                ->flowKey('authorization')
                ->stepName('finance_director'),

            ApprovalAction::makeReject()
                ->flowKey('authorization')
                ->stepName('finance_director'),

            ApprovalAction::makeReset()
                ->flowKey('authorization')
                ->stepName('finance_director'),
        ];
    }

    protected function getCollectionEditAction(): Action
    {
        /** @var Collection $record */
        $record = $this->getRecord();

        return EditAction::make()
            ->visible(fn () => ! $record->isVoided());
    }

    protected function getCollectionVoidAction(): Action
    {
        /** @var Collection $record */
        $record = $this->getRecord();

        return DangerRecordStatusAction::make('void')
            ->modalHeading(__('Void Collection'))
            ->schema([
                Textarea::make('voided_reason')
                    ->required()
                    ->maxLength(500),
            ])
            ->visible(fn () => ! $record->isVoided())
            ->applyTransitionUsing(function (Collection $record, array $data): void {
                $record->voided_at = now();
                $record->voided_reason = (string) ($data['voided_reason'] ?? '');
                $record->save();
            })
            ->afterTransitionUsing(function (Collection $record): void {
                CollectionVoided::dispatch($record);
            })
            ->notificationTitleUsing(fn (): string => __('Collection voided'))
            ->redirectUrlUsing(fn (Collection $record): string => $this->getResource()::getUrl('view', ['record' => $record]));
    }

    protected function getCollectionDuplicateAction(string $name = 'duplicate'): Action
    {
        return DuplicateRecordAction::make($name)
            ->exceptAttributes(['code', 'voided_at', 'voided_reason'])
            ->mutateRecordUsing(function (Collection $newCollection): void {
                $newCollection->collection_date = now()->toDateString();
            })
            ->successTitleUsing(fn (): string => __('Collection duplicated'))
            ->redirectUrlUsing(fn (Collection $newCollection): string => $this->getResource()::getUrl('edit', ['record' => $newCollection]));
    }
}
