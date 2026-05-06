<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Sales\Enums\SaleNoteStatusEnum;
use Modules\Sales\Models\SaleNote;
use Modules\Sales\Services\SaleNoteToInvoiceConverter;

trait InteractsWithSaleNoteHeaderActions
{
    protected function getSaleNoteIssueAction(): Action
    {
        return Action::make('issue')
            ->label(__('Issue'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->visible(fn (SaleNote $record) => $record->status === SaleNoteStatusEnum::Draft)
            ->action(fn (SaleNote $record) => $this->issueAction($record));
    }

    protected function getSaleNoteVoidAction(): Action
    {
        return Action::make('void')
            ->label(__('Void'))
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->visible(fn (SaleNote $record) => $record->status === SaleNoteStatusEnum::Issued && ! $record->isConvertible())
            ->form([
                TextInput::make('voided_reason')
                    ->label(__('Reason'))
                    ->required()
                    ->columnSpanFull(),
            ])
            ->action(fn (SaleNote $record, array $data) => $this->voidAction($record, $data));
    }

    protected function getSaleNoteConvertToInvoiceAction(): Action
    {
        return Action::make('convert_to_invoice')
            ->label(__('Convert to Invoice'))
            ->icon('heroicon-o-document-text')
            ->color('info')
            ->visible(fn (SaleNote $record) => $record->isConvertible())
            ->action(fn (SaleNote $record) => $this->convertToInvoiceAction($record));
    }

    protected function getSaleNoteGeneratePdfAction(): Action
    {
        return GeneratePdfAction::make()
            ->label(__('Generate PDF'));
    }

    private function issueAction(SaleNote $record): void
    {
        $record->update([
            'status' => SaleNoteStatusEnum::Issued,
            'issued_at' => now(),
        ]);

        Notification::make()
            ->success()
            ->title(__('Sale Note issued successfully'))
            ->send();
    }

    private function voidAction(SaleNote $record, array $data): void
    {
        $record->update([
            'status' => SaleNoteStatusEnum::Voided,
            'voided_at' => now(),
            'voided_reason' => $data['voided_reason'],
        ]);

        Notification::make()
            ->success()
            ->title(__('Sale Note voided successfully'))
            ->send();
    }

    private function convertToInvoiceAction(SaleNote $record): void
    {
        $converter = app(SaleNoteToInvoiceConverter::class);
        $invoice = $converter->convert($record);

        Notification::make()
            ->success()
            ->title(__('Converted to Invoice'))
            ->body(__('Invoice #:code created', ['code' => $invoice->code]))
            ->persistent()
            ->action(
                \Filament\Notifications\Actions\Action::make('view')
                    ->label(__('View'))
                    ->url(fn () => route('filament.core-app.resources.invoices.view', ['record' => $invoice->id]))
                    ->openUrlInNewTab()
            )
            ->send();
    }
}
