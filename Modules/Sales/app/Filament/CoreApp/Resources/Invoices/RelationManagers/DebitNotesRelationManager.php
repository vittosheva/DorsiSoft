<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Actions\OpenRecordAction;
use Modules\Core\Support\Actions\PreviewRecordAction;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\DebitNoteResource;
use Modules\Sales\Support\PreviewAmountFormatter;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Support\Tables\Columns\CommercialStatusColumn;
use Modules\Sri\Support\Tables\Columns\ElectronicStatusColumn;

final class DebitNotesRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'debitNotes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->electronic_status === ElectronicStatusEnum::Authorized;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Debit Notes');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Debit notes issued against this invoice to record additional charges, interest, or value corrections. Each debit note increases the total amount owed and must be independently authorized by the SRI. Debit notes are part of the complete audit trail for this invoice.'))
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with([
                'creator:id,name,avatar_url',
            ]))
            ->recordTitleAttribute('code')
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->date('d/m/Y')
                    ->sortable(),

                MoneyTextColumn::make('total')
                    ->label(__('Total'))
                    ->currencyCode(fn($record): string => $record->currency_code),

                CommercialStatusColumn::make(),

                ElectronicStatusColumn::make(),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([
                PreviewRecordAction::make()
                    ->modalHeading(__('Debit Note Preview'))
                    ->modalContent(fn($record): View => view('sales::filament.invoices.relation-managers.debit-note-preview', [
                        'record' => PreviewAmountFormatter::normalize($record, ['total', 'payment_amount']),
                    ])),
                OpenRecordAction::make()
                    ->url(fn($record): string => DebitNoteResource::getUrl('view', ['record' => $record]), shouldOpenInNewTab: true),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }
}
