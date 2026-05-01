<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Support\Actions\DangerRecordStatusAction;
use Modules\Core\Support\Actions\DuplicateRecordAction;
use Modules\Core\Support\Actions\TransitionRecordStatusAction;
use Modules\Sales\Enums\InvoiceStatusEnum;
use Modules\Sales\Enums\SalesOrderStatusEnum;
use Modules\Sales\Events\InvoiceVoided;
use Modules\Sales\Events\SaleConfirmed;
use Modules\Sales\Exceptions\OrderAlreadyInvoicedException;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\SalesOrderToInvoiceConverter;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Support\Components\FusedGroups\SequenceEmissionFusedGroup;
use Modules\Workflow\Enums\ApprovalFlowKey;
use Modules\Workflow\Filament\Actions\ApprovalAction;

trait InteractsWithSalesOrderHeaderActions
{
    /**
     * @return array<int, Action>
     */
    protected function getSalesOrderApprovalActions(): array
    {
        return [
            ApprovalAction::makeApprove()
                ->flowKey(ApprovalFlowKey::SalesOrderConfirmation->value)
                ->stepName('manager'),

            ApprovalAction::makeReject()
                ->flowKey(ApprovalFlowKey::SalesOrderConfirmation->value)
                ->stepName('manager'),

            ApprovalAction::makeReset()
                ->flowKey(ApprovalFlowKey::SalesOrderConfirmation->value)
                ->stepName('manager'),
        ];
    }

    protected function getSalesOrderConvertToInvoiceAction(): Action
    {
        /** @var SalesOrder $record */
        $record = $this->getRecord();

        return Action::make('convert_invoice')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('success')
            ->modalHeading(__('Select invoice sequence emission'))
            ->schema([
                Section::make()
                    ->schema([
                        Checkbox::make('create_invoice')
                            ->label(__('Create invoice?'))
                            ->helperText(__('If enabled, an invoice will be created from this sales order upon confirmation.'))
                            ->live(),
                    ]),
                SequenceEmissionFusedGroup::makeForDocumentType(SriDocumentTypeEnum::Invoice)
                    ->visibleJs(<<<'JS'
                        !! $get('create_invoice')
                    JS),
            ])
            ->visible(function () use ($record): bool {
                if ($record->isApprovalRequired('sales_order_confirmation') && ! $record->isApproved('sales_order_confirmation')) {
                    return false;
                }

                return $record->status->isInvoiceable();
            })
            ->action(function (array $data) use ($record): void {
                if (! (bool) ($data['create_invoice'] ?? true)) {
                    if ($record->status === SalesOrderStatusEnum::Pending) {
                        $record->status = SalesOrderStatusEnum::Confirmed;
                        $record->save();
                    }

                    Notification::make()
                        ->success()
                        ->title(__('Order confirmed'))
                        ->body(__('Invoice creation skipped for sales order :order.', ['order' => $record->code]))
                        ->persistent()
                        ->send();

                    return;
                }

                try {
                    $invoice = app(SalesOrderToInvoiceConverter::class)->convert($record, $data);
                } catch (OrderAlreadyInvoicedException $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('Invoice already exists'))
                        ->body(__('This order already has an invoice associated.'))
                        ->send();

                    throw new Halt;
                }

                Notification::make()
                    ->success()
                    ->title(__('Invoice created'))
                    ->body(__('Invoice :code created from sales order :order.', ['code' => $invoice->code, 'order' => $record->code]))
                    ->persistent()
                    ->actions([
                        Action::make('view_invoice')
                            ->button()
                            ->url(
                                InvoiceResource::getUrl('view', ['record' => $invoice]),
                                shouldOpenInNewTab: true
                            ),
                    ])
                    ->send();
            })
            ->requiresConfirmation()
            ->modalWidth(Width::TwoExtraLarge);
    }

    protected function getSalesOrderDuplicateAction(string $name = 'duplicate'): Action
    {
        return DuplicateRecordAction::make($name)
            ->exceptAttributes(['code', 'status'])
            ->mutateRecordUsing(function (SalesOrder $newOrder): void {
                $newOrder->status = SalesOrderStatusEnum::Pending;
                $newOrder->issue_date = now()->toDateString();
            })
            ->duplicateRelationsUsing(function (SalesOrder $order, SalesOrder $newOrder): void {
                foreach ($order->items as $item) {
                    $newItem = $item->replicate();
                    $newItem->order_id = $newOrder->getKey();
                    $newItem->save();
                }
            })
            ->successTitleUsing(fn (): string => __('Order duplicated'))
            ->redirectUrlUsing(fn (SalesOrder $newOrder): string => $this->getResource()::getUrl('edit', ['record' => $newOrder]));
    }

    /**
     * @return array<int, Action>
     */
    protected function getSalesOrderWorkflowActions(): array
    {
        /** @var SalesOrder $record */
        $record = $this->getRecord();

        return [
            TransitionRecordStatusAction::make('confirm')
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->visible(fn () => $record->status === SalesOrderStatusEnum::Pending
                    && (! $record->isApprovalRequired('sales_order_confirmation') || $record->isApproved('sales_order_confirmation')))
                ->applyTransitionUsing(function (SalesOrder $record): void {
                    $record->status = SalesOrderStatusEnum::Confirmed;
                    $record->save();
                })
                ->afterTransitionUsing(function (SalesOrder $record): void {
                    SaleConfirmed::dispatch(
                        $record->company_id,
                        $record->getKey(),
                        $record->items->toArray(),
                    );
                })
                ->notificationTitleUsing(fn (): string => __('Order confirmed'))
                ->redirectUrlUsing(fn (SalesOrder $record): string => $this->getResource()::getUrl('view', ['record' => $record])),
            DangerRecordStatusAction::make('cancel')
                ->visible(fn () => in_array($record->status, [SalesOrderStatusEnum::Pending, SalesOrderStatusEnum::Confirmed, SalesOrderStatusEnum::PartiallyInvoiced], true))
                ->applyTransitionUsing(function (SalesOrder $record): void {
                    $hasActiveInvoice = $record->invoices()
                        ->whereIn('status', [InvoiceStatusEnum::Issued->value, InvoiceStatusEnum::Paid->value])
                        ->exists();

                    if ($hasActiveInvoice) {
                        Notification::make()
                            ->danger()
                            ->title(__('Cannot cancel order'))
                            ->body(__('This order has an issued or paid invoice. Void the invoice before cancelling the order.'))
                            ->persistent()
                            ->send();

                        throw new Halt;
                    }

                    $voidedInvoice = null;

                    DB::transaction(function () use ($record, &$voidedInvoice): void {
                        $draft = $record->invoices()
                            ->where('status', InvoiceStatusEnum::Draft->value)
                            ->first();

                        if ($draft) {
                            $draft->status = InvoiceStatusEnum::Voided;
                            $draft->save();
                            $voidedInvoice = $draft;
                        }

                        $record->status = SalesOrderStatusEnum::Cancelled;
                        $record->save();
                    });

                    if ($voidedInvoice) {
                        InvoiceVoided::dispatch($voidedInvoice);
                    }
                })
                ->notificationTitleUsing(fn (): string => __('Order cancelled'))
                ->redirectUrlUsing(fn (SalesOrder $record): string => $this->getResource()::getUrl('view', ['record' => $record])),
            TransitionRecordStatusAction::make('complete')
                ->label(__('Mark as Completed'))
                ->icon(Heroicon::CheckBadge)
                ->color('info')
                ->visible(fn () => $record->status === SalesOrderStatusEnum::FullyInvoiced)
                ->applyTransitionUsing(function (SalesOrder $record): void {
                    $record->status = SalesOrderStatusEnum::Completed;
                    $record->save();
                })
                ->notificationTitleUsing(fn (): string => __('Order completed'))
                ->redirectUrlUsing(fn (SalesOrder $record): string => $this->getResource()::getUrl('view', ['record' => $record])),
        ];
    }
}
