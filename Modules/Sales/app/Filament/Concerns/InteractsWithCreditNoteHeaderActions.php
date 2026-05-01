<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Modules\Core\Support\Actions\DuplicateRecordAction;
use Modules\Core\Support\Actions\TransitionRecordStatusAction;
use Modules\Finance\Enums\CollectionMethodEnum;
use Modules\Finance\Models\Collection;
use Modules\Finance\Services\AllocateCollectionToInvoiceService;
use Modules\Finance\Support\CollectionAllocationMath;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\DocumentIssuanceService;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Workflow\Enums\ApprovalFlowKey;
use Modules\Workflow\Filament\Actions\ApprovalAction;

trait InteractsWithCreditNoteHeaderActions
{
    /**
     * @return array<int, Action>
     */
    protected function getCreditNoteApprovalActions(): array
    {
        return [
            ApprovalAction::makeApprove()
                ->flowKey(ApprovalFlowKey::CreditNoteIssuance->value)
                ->stepName('finance_director'),

            ApprovalAction::makeReject()
                ->flowKey(ApprovalFlowKey::CreditNoteIssuance->value)
                ->stepName('finance_director'),

            ApprovalAction::makeReset()
                ->flowKey(ApprovalFlowKey::CreditNoteIssuance->value)
                ->stepName('finance_director'),
        ];
    }

    protected function getCreditNoteIssueAction(): Action
    {
        /** @var CreditNote $record */
        $record = $this->getRecord();

        return TransitionRecordStatusAction::make('issue')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(function () use ($record): bool {
                $key = ApprovalFlowKey::CreditNoteIssuance->value;

                if ($record->isApprovalRequired($key) && ! $record->isApproved($key)) {
                    return false;
                }

                return $record->status === CreditNoteStatusEnum::Draft;
            })
            ->applyTransitionUsing(function (CreditNote $record, DocumentIssuanceService $issuanceService): void {
                $this->guardCreditNoteInvoiceLimit($record);
                try {
                    $issuanceService->issueCreditNote($record);
                } catch (InvalidArgumentException|XmlGenerationException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('Cannot issue credit note'))
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();

                    throw new Halt;
                }
            })
            ->notificationTitleUsing(fn (): string => __('Credit note issued'))
            ->redirectUrlUsing(fn (CreditNote $record): string => $this->getResource()::getUrl('view', ['record' => $record]));
    }

    protected function getCreditNoteDuplicateAction(string $name = 'duplicate'): Action
    {
        return DuplicateRecordAction::make($name)
            ->exceptAttributes(['code', 'status', 'issue_date', 'voided_at', 'voided_reason', 'applied_amount', 'refunded_amount'])
            ->mutateRecordUsing(function (CreditNote $newCreditNote): void {
                $newCreditNote->status = CreditNoteStatusEnum::Draft;
            })
            ->withItemsAndTaxes('credit_note_id', 'credit_note_item_id')
            ->successTitleUsing(fn (): string => __('Credit Note Duplicated'))
            ->redirectUrlUsing(fn (CreditNote $newCreditNote): string => $this->getResource()::getUrl('edit', ['record' => $newCreditNote]));
    }

    protected function getCreditNoteRegisterCollectionAction(): Action
    {
        /** @var CreditNote $record */
        $record = $this->getRecord();

        return Action::make('registerCreditNoteCollection')
            ->label(__('Register Credit as Collection'))
            ->icon(Heroicon::Banknotes)
            ->color('info')
            ->visible(function () use ($record): bool {
                if ($record->status === CreditNoteStatusEnum::Draft || $record->status === CreditNoteStatusEnum::Voided) {
                    return false;
                }

                if ($record->electronic_status !== ElectronicStatusEnum::Authorized) {
                    return false;
                }

                return bccomp($record->getAvailableCollectionBalance(), '0.0000', CollectionAllocationMath::SCALE) > 0;
            })
            ->action(function () use ($record): void {
                $existingCollection = Collection::query()
                    ->where('credit_note_id', $record->getKey())
                    ->first();

                if ($existingCollection) {
                    Notification::make()
                        ->warning()
                        ->title(__('Credit note is already registered in collections'))
                        ->send();

                    return;
                }

                $collection = Collection::query()->create([
                    'company_id' => $record->company_id,
                    'business_partner_id' => $record->business_partner_id,
                    'credit_note_id' => $record->getKey(),
                    'customer_name' => $record->customer_name,
                    'collection_date' => now()->toDateString(),
                    'amount' => $record->getAvailableCollectionBalance(),
                    'currency_code' => $record->currency_code,
                    'collection_method' => CollectionMethodEnum::CreditNote,
                    'reference_number' => $record->code,
                    'notes' => __('Auto-generated from credit note :code', ['code' => $record->code]),
                ]);

                if ($record->invoice_id) {
                    try {
                        /** @var AllocateCollectionToInvoiceService $allocationService */
                        $allocationService = app(AllocateCollectionToInvoiceService::class);
                        $allocationService->allocate(
                            collection: $collection,
                            invoiceId: (int) $record->invoice_id,
                            amount: (string) $collection->amount,
                        );
                    } catch (InvalidArgumentException) {
                        // Invoice may already be covered or have no pending balance; user can allocate manually.
                    }
                }

                Notification::make()
                    ->success()
                    ->title(__('Credit note registered in collections'))
                    ->send();
            });
    }

    /**
     * @throws Halt
     */
    protected function guardCreditNoteInvoiceLimit(CreditNote $creditNote): void
    {
        if (! $creditNote->invoice_id) {
            return;
        }

        $invoice = Invoice::find($creditNote->invoice_id, ['id', 'total']);

        if (! $invoice) {
            return;
        }

        $availableLimit = $invoice->availableCreditableAmount($creditNote->getKey());

        if (CollectionAllocationMath::exceedsWithTolerance((string) $creditNote->total, $availableLimit)) {
            Notification::make()
                ->danger()
                ->title(__('Cannot issue credit note'))
                ->body(__('The credit note total (:total) exceeds the remaining creditable amount on the invoice (:available).', [
                    'total' => number_format((float) $creditNote->total, 2),
                    'available' => number_format((float) $availableLimit, 2),
                ]))
                ->send();

            throw new Halt;
        }
    }
}
