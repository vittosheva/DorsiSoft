<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\Actions\GeneratePdfAction;
use Modules\Core\Support\Actions\GeneratePdfBulkAction;
use Modules\Core\Support\Actions\SendDocumentEmailAction;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\CustomerNameTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Core\Support\Tables\Filters\DateRangeFilter;
use Modules\Core\Support\Tables\Filters\StatusFilter;
use Modules\Sales\Enums\CreditNoteStatusEnum;
use Modules\Sales\Support\Tables\Columns\SriSequentialTextColumn;
use Modules\Sales\Support\Tables\Filters\CustomerFilter;
use Modules\Sri\Support\Tables\Columns\CommercialStatusColumn;
use Modules\Sri\Support\Tables\Columns\ElectronicStatusColumn;
use Modules\Workflow\Support\Tables\Columns\ApprovalDecisionTextColumn;

final class CreditNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Credit notes issued by this company to correct or partially reverse previously issued invoices. Each credit note references the original invoice and the reason for the adjustment. Credit notes must be authorized by the SRI and can be applied against the customer\'s outstanding balance.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                SriSequentialTextColumn::make(),

                TextColumn::make('issue_date')
                    ->date('d/m/Y')
                    ->sortable(),

                CustomerNameTextColumn::make('customer_name'),

                TextColumn::make('invoice.code')
                    ->placeholder('—')
                    ->description(fn ($record) => $record->invoice ? $record->invoice->getSriSequentialCode() : null),

                MoneyTextColumn::make('total')
                    ->currencyCode(fn ($record): string => $record->currency_code),

                MoneyTextColumn::make('applied_amount')
                    ->currencyCode(fn ($record): string => $record->currency_code),

                MoneyTextColumn::make('refunded_amount')
                    ->currencyCode(fn ($record): string => $record->currency_code),

                CommercialStatusColumn::make(),

                ElectronicStatusColumn::make(),

                ApprovalDecisionTextColumn::forFlow('issuance_approval', 'credit_note_issuance'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                CustomerFilter::make('customer'),
                DateRangeFilter::make('issue_date'),
                StatusFilter::make('status')
                    ->options(CreditNoteStatusEnum::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->status === CreditNoteStatusEnum::Draft),
                SendDocumentEmailAction::make(),
                GeneratePdfAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->status === CreditNoteStatusEnum::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    GeneratePdfBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
