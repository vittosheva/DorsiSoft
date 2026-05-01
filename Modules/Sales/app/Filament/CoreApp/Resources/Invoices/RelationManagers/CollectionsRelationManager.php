<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Actions\OpenRecordAction;
use Modules\Core\Support\Actions\PreviewRecordAction;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Finance\Filament\CoreApp\Resources\Collections\CollectionResource;
use Modules\Sales\Support\PreviewAmountFormatter;

final class CollectionsRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'allocations';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Collections Applied');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Payment collections applied to this invoice, showing the amounts matched from customer payments. Each allocation reduces the outstanding balance of the invoice. Multiple partial payments can be applied over time until the invoice is fully settled.'))
            // ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('creator:id,name,avatar_url'))
            ->recordTitleAttribute('collection.code')
            ->columns([
                TextColumn::make('collection.code')
                    ->label(__('Collection'))
                    ->weight(FontWeight::Medium),

                TextColumn::make('collection.collection_date')
                    ->label(__('Date'))
                    ->date('d/m/Y'),

                TextColumn::make('collection.collection_method')
                    ->label(__('Method'))
                    ->badge(),

                TextColumn::make('collection.reference_number')
                    ->label(__('Reference'))
                    ->placeholder('—'),

                MoneyTextColumn::make('amount')
                    ->label(__('Applied'))
                    ->currencyCode(fn (): string => $this->getOwnerRecord()->currency_code ?? 'USD'),

                TextColumn::make('collection.voided_at')
                    ->label(__('Status'))
                    ->badge()
                    ->default(fn ($record) => $record->collection?->isVoided() ? __('Voided') : __('Active'))
                    ->color(fn ($record) => $record->collection?->isVoided() ? 'danger' : 'success'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([
                PreviewRecordAction::make()
                    ->modalHeading(__('Collection Allocation Preview'))
                    ->modalContent(fn ($record): View => view('sales::filament.invoices.relation-managers.collection-allocation-preview', [
                        'record' => PreviewAmountFormatter::normalize($record, ['amount']),
                    ])),
                OpenRecordAction::make()
                    ->visible(fn ($record): bool => (bool) $record->collection)
                    ->url(fn ($record): ?string => $record->collection
                        ? CollectionResource::getUrl('view', ['record' => $record->collection])
                        : null, shouldOpenInNewTab: true),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
