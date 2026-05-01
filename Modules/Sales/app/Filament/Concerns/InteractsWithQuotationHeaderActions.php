<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Actions\DangerRecordStatusAction;
use Modules\Core\Support\Actions\DuplicateRecordAction;
use Modules\Core\Support\Actions\TransitionRecordStatusAction;
use Modules\Sales\Enums\QuotationStatusEnum;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\SalesOrderResource;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Services\QuotationToOrderConverter;

trait InteractsWithQuotationHeaderActions
{
    protected function getQuotationDuplicateAction(string $name = 'duplicate'): Action
    {
        return DuplicateRecordAction::make($name)
            ->exceptAttributes(['code', 'status', 'sent_at', 'accepted_at', 'order_id'])
            ->mutateRecordUsing(function (Quotation $newQuotation, Quotation $quotation): void {
                $newQuotation->status = QuotationStatusEnum::Draft;
                $newQuotation->issue_date = now()->toDateString();
                $newQuotation->expires_at = now()->addDays($quotation->validity_days)->toDateString();
            })
            ->withItemsAndTaxes('quotation_id', 'quotation_item_id')
            ->successTitleUsing(fn (): string => __('Quotation duplicated'))
            ->redirectUrlUsing(fn (Quotation $newQuotation): string => $this->getResource()::getUrl('edit', ['record' => $newQuotation]));
    }

    /**
     * @return array<int, Action>
     */
    protected function getQuotationDecisionActions(): array
    {
        /** @var Quotation $record */
        $record = $this->getRecord();

        return [
            TransitionRecordStatusAction::make('accept')
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->visible(fn () => in_array($record->status, [QuotationStatusEnum::Draft, QuotationStatusEnum::Sent], true))
                ->modalHeading(__('Accept Quotation'))
                ->modalSubmitActionLabel(__('Accept'))
                ->schema([
                    Section::make()
                        ->schema([
                            Checkbox::make('create_sales_order')
                                ->label(__('Create sales order?'))
                                ->helperText(__('If enabled, a sales order will be created from this quotation upon acceptance.')),
                        ]),
                ])
                ->action(function (array $data) use ($record): void {
                    if ($data['create_sales_order']) {
                        $order = app(QuotationToOrderConverter::class)->convert($record);

                        Notification::make()
                            ->success()
                            ->title(__('Quotation accepted'))
                            ->body(__('Sales order :code created', ['code' => $order->code]))
                            ->actions([
                                Action::make('view_sales_order')
                                    ->button()
                                    ->url(
                                        SalesOrderResource::getUrl('view', ['record' => $order]),
                                        shouldOpenInNewTab: true
                                    ),
                            ])
                            ->persistent()
                            ->send();
                    } else {
                        $record->status = QuotationStatusEnum::Accepted;
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title(__('Quotation accepted'))
                            ->persistent()
                            ->send();
                    }
                })
                ->requiresConfirmation(),
            DangerRecordStatusAction::make('reject')
                ->visible(fn () => in_array($record->status, [QuotationStatusEnum::Draft, QuotationStatusEnum::Sent], true))
                ->applyTransitionUsing(function (Quotation $record): void {
                    $record->status = QuotationStatusEnum::Rejected;
                    $record->save();
                })
                ->notificationTitleUsing(fn (): string => __('Quotation rejected'))
                ->redirectUrlUsing(fn (Quotation $record): string => $this->getResource()::getUrl('view', ['record' => $record])),
        ];
    }
}
