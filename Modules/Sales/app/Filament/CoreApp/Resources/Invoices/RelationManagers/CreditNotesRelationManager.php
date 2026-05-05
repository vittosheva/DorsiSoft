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
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\CreditNoteResource;
use Modules\Sales\Support\PreviewAmountFormatter;
use Modules\Sri\Enums\ElectronicStatusEnum;
use Modules\Sri\Support\Tables\Columns\CommercialStatusColumn;
use Modules\Sri\Support\Tables\Columns\ElectronicStatusColumn;

final class CreditNotesRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'creditNotes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->electronic_status === ElectronicStatusEnum::Authorized;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Credit Notes');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Credit notes issued against this invoice to partially or fully reverse its value. Each credit note documents the reason for the adjustment and the amount credited to the customer. Credit notes affect the net balance of this invoice and must be authorized by the SRI.'))
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with('creator:id,name,avatar_url'))
            ->recordTitleAttribute('code')
            ->columns([
                CodeTextColumn::make('code')
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label(__('Reason'))
                    ->limit(50)
                    ->placeholder('—'),

                MoneyTextColumn::make('total')
                    ->label(__('Total'))
                    ->currencyCode(fn($record): string => $record->currency_code),

                MoneyTextColumn::make('applied_amount')
                    ->label(__('Applied'))
                    ->currencyCode(fn($record): string => $record->currency_code),

                MoneyTextColumn::make('refunded_amount')
                    ->label(__('Refunded'))
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
                    ->modalHeading(__('Credit Note Preview'))
                    ->modalContent(fn($record): View => view('sales::filament.invoices.relation-managers.credit-note-preview', [
                        'record' => PreviewAmountFormatter::normalize($record, ['total', 'applied_amount', 'refunded_amount']),
                    ])),
                OpenRecordAction::make()
                    ->url(fn($record): string => CreditNoteResource::getUrl('view', ['record' => $record]), shouldOpenInNewTab: true),
            ])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }
}
