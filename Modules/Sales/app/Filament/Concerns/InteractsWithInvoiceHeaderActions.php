<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Support\Actions\DuplicateRecordAction;
use Modules\Core\Support\Actions\TransitionRecordStatusAction;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Finance\Enums\CollectionMethodEnum;
use Modules\Finance\Models\Collection;
use Modules\Finance\Models\CollectionAllocation;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Services\DocumentIssuanceService;
use Modules\Sri\Exceptions\XmlGenerationException;
use Modules\Workflow\Enums\ApprovalFlowKey;
use Modules\Workflow\Filament\Actions\ApprovalAction;

trait InteractsWithInvoiceHeaderActions
{
    /**
     * @return array<int, Action>
     */
    protected function getInvoiceApprovalActions(): array
    {
        return [
            ApprovalAction::makeApprove()
                ->flowKey(ApprovalFlowKey::InvoiceIssuance->value)
                ->stepName('manager'),

            ApprovalAction::makeReject()
                ->flowKey(ApprovalFlowKey::InvoiceIssuance->value)
                ->stepName('manager'),

            ApprovalAction::makeReset()
                ->flowKey(ApprovalFlowKey::InvoiceIssuance->value)
                ->stepName('manager'),
        ];
    }

    protected function getInvoiceMarkPaidAction(): Action
    {
        /** @var Invoice $record */
        $record = $this->getRecord();

        return Action::make('mark_paid')
            ->icon(Heroicon::Banknotes)
            ->color('info')
            ->visible(fn () => $record->status === InvoiceStatusEnum::Issued)
            ->modalHeading(__('Record collection'))
            ->schema([
                MoneyTextInput::make('amount')
                    ->currencyCode(fn (): string => $record->currency_code ?? 'USD')
                    ->step('0.0001')
                    ->required()
                    ->minValue(0.0001)
                    ->default(fn () => $record->total - $record->paid_amount),

                Select::make('collection_method')
                    ->options(CollectionMethodEnum::class)
                    ->required()
                    ->default(CollectionMethodEnum::BankTransfer->value),

                TextInput::make('reference_number')
                    ->nullable()
                    ->maxLength(100),
            ])
            ->action(function (array $data) use ($record): void {
                DB::transaction(function () use ($data, $record): void {
                    $freshInvoice = Invoice::query()
                        ->lockForUpdate()
                        ->findOrFail($record->getKey());

                    $pendingBalance = bcsub(
                        (string) $freshInvoice->total,
                        (string) $freshInvoice->paid_amount,
                        4,
                    );

                    if (bccomp((string) $data['amount'], $pendingBalance, 4) > 0) {
                        throw new InvalidArgumentException(__('The amount cannot exceed the pending invoice balance.'));
                    }

                    $collection = Collection::create([
                        'company_id' => $freshInvoice->company_id,
                        'business_partner_id' => $freshInvoice->business_partner_id,
                        'customer_name' => $freshInvoice->customer_name,
                        'collection_date' => now()->toDateString(),
                        'amount' => $data['amount'],
                        'currency_code' => $freshInvoice->currency_code,
                        'collection_method' => $data['collection_method'],
                        'reference_number' => $data['reference_number'] ?? null,
                    ]);

                    CollectionAllocation::create([
                        'company_id' => $freshInvoice->company_id,
                        'collection_id' => $collection->getKey(),
                        'invoice_id' => $freshInvoice->getKey(),
                        'amount' => $data['amount'],
                        'allocated_at' => now(),
                    ]);
                });

                Notification::make()
                    ->success()
                    ->title(__('Collection recorded'))
                    ->send();

                $this->redirect($this->getResource()::getUrl('view', ['record' => $record]));
            });
    }

    protected function getInvoiceIssueAction(): Action
    {
        return TransitionRecordStatusAction::make('issue')
            ->icon(Heroicon::CheckCircle)
            ->color('success')
            ->visible(function (): bool {
                /** @var Invoice $record */
                $record = $this->getRecord();

                $key = ApprovalFlowKey::InvoiceIssuance->value;

                if ($record->isApprovalRequired($key) && ! $record->isApproved($key)) {
                    return false;
                }

                return $record->status === InvoiceStatusEnum::Draft;
            })
            ->applyTransitionUsing(function (Invoice $record, DocumentIssuanceService $issuanceService): void {
                try {
                    $issuanceService->issueInvoice(
                        $record,
                        Auth::id(),
                    );
                } catch (InvalidArgumentException|XmlGenerationException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('Cannot issue invoice'))
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();

                    throw new Halt;
                }
            })
            ->notificationTitleUsing(fn (): string => __('Invoice issued'))
            ->redirectUrlUsing(fn (Invoice $record): string => $this->getResource()::getUrl('view', ['record' => $record]));
    }

    protected function getInvoiceDuplicateAction(string $name = 'duplicate'): Action
    {
        return DuplicateRecordAction::make($name)
            ->exceptAttributes(['code', 'status', 'issue_date', 'voided_at', 'voided_reason', 'sales_order_id'])
            ->mutateRecordUsing(function (Invoice $newInvoice): void {
                $newInvoice->status = InvoiceStatusEnum::Draft;
                $newInvoice->issue_date = now()->toDateString();
            })
            ->withItemsAndTaxes('invoice_id', 'invoice_item_id')
            ->successTitleUsing(fn (): string => __('Invoice Duplicated'))
            ->redirectUrlUsing(fn (Invoice $newInvoice): string => $this->getResource()::getUrl('edit', ['record' => $newInvoice]));
    }
}
