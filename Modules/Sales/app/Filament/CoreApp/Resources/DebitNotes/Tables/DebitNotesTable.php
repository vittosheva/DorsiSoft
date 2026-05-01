<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Tables;

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
use Modules\Sales\Enums\DebitNoteStatusEnum;
use Modules\Sales\Support\Tables\Columns\SriSequentialTextColumn;
use Modules\Sales\Support\Tables\Filters\CustomerFilter;
use Modules\Sri\Support\Tables\Columns\CommercialStatusColumn;
use Modules\Sri\Support\Tables\Columns\ElectronicStatusColumn;

final class DebitNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Debit notes issued by this company to increase the value of previously issued invoices due to interest charges, additional costs, or corrections. Each debit note references the original invoice and the reason for the increase. Debit notes are electronically authorized by the SRI.'))
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                SriSequentialTextColumn::make(),

                TextColumn::make('issue_date')
                    ->label(__('Issue date'))
                    ->date('d/m/Y')
                    ->sortable(),

                CustomerNameTextColumn::make('customer_name'),

                TextColumn::make('invoice.code')
                    ->label(__('Invoice'))
                    ->placeholder('—')
                    ->description(fn ($record) => $record->invoice ? $record->invoice->getSriSequentialCode() : null),

                MoneyTextColumn::make('total')
                    ->currencyCode(fn ($record): string => $record->currency_code),

                CommercialStatusColumn::make(),

                ElectronicStatusColumn::make(),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([
                CustomerFilter::make('customer'),
                DateRangeFilter::make('issue_date'),
                StatusFilter::make('status')
                    ->options(DebitNoteStatusEnum::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->status === DebitNoteStatusEnum::Draft),
                SendDocumentEmailAction::make(),
                GeneratePdfAction::make(),
                DeleteAction::make()
                    ->visible(fn ($record) => $record->status === DebitNoteStatusEnum::Draft),
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
