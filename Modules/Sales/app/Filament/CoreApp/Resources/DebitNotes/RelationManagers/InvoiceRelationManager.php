<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DebitNotes\RelationManagers;

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
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sales\Support\PreviewAmountFormatter;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Support\Tables\Columns\CommercialStatusColumn;
use Modules\Sri\Support\Tables\Columns\ElectronicStatusColumn;

final class InvoiceRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'invoice';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->electronic_status === ElectronicStatusEnum::Authorized;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Related Invoice');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('The original invoice to which this debit note is linked. The referenced invoice serves as the fiscal basis for the additional charges or corrections recorded in this debit note. Only one invoice can be referenced per debit note.'))
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
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
                    ->currencyCode(fn ($record): string => $record->currency_code),

                CommercialStatusColumn::make(),

                ElectronicStatusColumn::make(),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([
                PreviewRecordAction::make()
                    ->modalHeading(__('Invoice Preview'))
                    ->modalContent(fn ($record): View => view('sales::filament.invoices.relation-managers.invoice-preview', [
                        'record' => PreviewAmountFormatter::normalize($record, ['total', 'paid_amount', 'credited_amount']),
                    ])),
                OpenRecordAction::make()
                    ->url(fn ($record): string => InvoiceResource::getUrl('view', ['record' => $record]), shouldOpenInNewTab: true),
            ])
            ->toolbarActions([])
            ->defaultSort('issue_date', 'desc');
    }
}
