<?php

declare(strict_types=1);

namespace Modules\Sri\Support\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\Invoice;
use Modules\Sri\Contracts\HasElectronicBilling;
use Modules\Sri\Enums\ElectronicCorrectionStatusEnum;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Services\ElectronicDocumentCorrectionClassifier;
use Modules\Sri\Services\ElectronicEventLogger;

final class CorrectRejectedElectronicDocumentAction extends Action
{
    private ?string $resourceClass = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Correct document'))
            ->tooltip(fn (Action $action) => $action->isIconButton() ? __('Correct document') : null)
            ->icon(Heroicon::PencilSquare)
            ->color('warning')
            ->visible(function (?Model $record): bool {
                if (! $record instanceof HasElectronicBilling || ! method_exists($record, 'canCorrectRejectedElectronicDocument')) {
                    return false;
                }

                return $record->canCorrectRejectedElectronicDocument();
            })
            ->authorize(function (?Model $record): bool {
                return Gate::allows('correctRejected', $record);
            })
            ->requiresConfirmation()
            ->modalHeading(__('Correct document'))
            ->modalDescription(__('A new draft will be created linked to the returned document, with a new sequence and access key in the next SRI cycle.'))
            ->action(function (?Model $record): void {
                if (! $record instanceof HasElectronicBilling || ! method_exists($record, 'canCorrectRejectedElectronicDocument')) {
                    return;
                }

                if (! $record->canCorrectRejectedElectronicDocument()) {
                    Notification::make()
                        ->title(__('Correction not available'))
                        ->body(__('This document only allows for a technical retry or has already been replaced by a correction.'))
                        ->warning()
                        ->persistent()
                        ->send();

                    return;
                }

                $classifier = app(ElectronicDocumentCorrectionClassifier::class);
                $triggeredBy = Auth::id();

                $newRecord = DB::transaction(function () use ($classifier, $record, $triggeredBy): Model {
                    $newRecord = $record->replicate($this->exceptAttributes());

                    $newRecord->code = null;
                    $newRecord->status = $record->getDraftCommercialStatus();
                    $newRecord->sequential_number = null;
                    $newRecord->access_key = null;
                    $newRecord->issue_date = $record->issue_date ?? now()->toDateString();
                    $newRecord->voided_at = null;
                    $newRecord->voided_reason = null;
                    $newRecord->electronic_status = null;
                    $newRecord->electronic_submitted_at = null;
                    $newRecord->electronic_authorized_at = null;
                    $newRecord->correction_status = ElectronicCorrectionStatusEnum::InProgress;
                    $newRecord->correction_source_id = $record->getKey();
                    $newRecord->superseded_by_id = null;
                    $newRecord->correction_requested_at = now();
                    $newRecord->corrected_at = null;
                    $newRecord->correction_reason = $classifier->summarize($record);
                    $newRecord->save();

                    $this->duplicateRelations($record, $newRecord);

                    $record->forceFill([
                        'correction_status' => ElectronicCorrectionStatusEnum::Superseded,
                        'superseded_by_id' => $newRecord->getKey(),
                        'corrected_at' => now(),
                        'correction_reason' => $record->correction_reason ?: $classifier->summarize($record),
                    ])->save();

                    ElectronicEventLogger::record(
                        document: $record,
                        event: 'correction_superseded',
                        statusFrom: $record->getElectronicStatus() ?? ElectronicStatusEnum::Rejected,
                        statusTo: $record->getElectronicStatus() ?? ElectronicStatusEnum::Rejected,
                        payload: [
                            'replacement_document_id' => $newRecord->getKey(),
                            'replacement_document_type' => $newRecord::class,
                        ],
                        triggeredBy: $triggeredBy,
                    );

                    if ($newRecord instanceof HasElectronicBilling) {
                        ElectronicEventLogger::record(
                            document: $newRecord,
                            event: 'correction_created',
                            statusFrom: null,
                            statusTo: null,
                            payload: [
                                'source_document_id' => $record->getKey(),
                                'source_document_type' => $record::class,
                            ],
                            triggeredBy: $triggeredBy,
                        );
                    }

                    return $newRecord;
                });

                Notification::make()
                    ->title(__('Correction created'))
                    ->body(__('A new draft was generated to correct and resend the receipt.'))
                    ->success()
                    ->send();

                if ($this->resourceClass !== null) {
                    $this->redirect($this->resourceClass::getUrl('edit', ['record' => $newRecord]));
                }
            });
    }

    public static function getDefaultName(): ?string
    {
        return 'correct_rejected_document';
    }

    public function resource(string $resourceClass): static
    {
        $this->resourceClass = $resourceClass;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    private function exceptAttributes(): array
    {
        return [
            'code',
            'status',
            'sequential_number',
            'access_key',
            'issue_date',
            'voided_at',
            'voided_reason',
            'electronic_status',
            'electronic_submitted_at',
            'electronic_authorized_at',
            'correction_status',
            'correction_source_id',
            'superseded_by_id',
            'correction_requested_at',
            'corrected_at',
            'correction_reason',
        ];
    }

    private function duplicateRelations(Model $record, Model $newRecord): void
    {
        match (true) {
            $record instanceof Invoice => $this->duplicateInvoiceRelations($record, $newRecord),
            $record instanceof CreditNote => $this->duplicateCreditNoteRelations($record, $newRecord),
            method_exists($record, 'items') => $this->duplicateItemsOnlyRelations($record, $newRecord),
            default => null,
        };
    }

    private function duplicateInvoiceRelations(Invoice $record, Model $newRecord): void
    {
        foreach ($record->items()->with('taxes')->get() as $item) {
            $newItem = $item->replicate();
            $newItem->invoice_id = $newRecord->getKey();
            $newItem->save();

            foreach ($item->taxes as $tax) {
                $newTax = $tax->replicate();
                $newTax->invoice_item_id = $newItem->getKey();
                $newTax->save();
            }
        }
    }

    private function duplicateCreditNoteRelations(CreditNote $record, Model $newRecord): void
    {
        foreach ($record->items()->with('taxes')->get() as $item) {
            $newItem = $item->replicate();
            $newItem->credit_note_id = $newRecord->getKey();
            $newItem->save();

            foreach ($item->taxes as $tax) {
                $newTax = $tax->replicate();
                $newTax->credit_note_item_id = $newItem->getKey();
                $newTax->save();
            }
        }
    }

    private function duplicateItemsOnlyRelations(Model $record, Model $newRecord): void
    {
        $relation = $record->items();
        $foreignKey = $relation->getForeignKeyName();

        foreach ($relation->get() as $item) {
            $newItem = $item->replicate();
            $newItem->{$foreignKey} = $newRecord->getKey();
            $newItem->save();
        }
    }
}
