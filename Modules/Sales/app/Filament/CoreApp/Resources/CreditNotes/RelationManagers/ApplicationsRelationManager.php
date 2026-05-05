<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\CreditNotes\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\MoneyTextColumn;
use Modules\Sri\Enums\ElectronicStatusEnum;

final class ApplicationsRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'applications';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->electronic_status === ElectronicStatusEnum::Authorized;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Applied to Invoices');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Invoices to which this credit note has been applied, showing the amounts credited against each outstanding balance. A credit note can be applied in full or in parts across multiple invoices. Each application reduces the open balance of the matched invoice accordingly.'))
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('creator:id,name,avatar_url'))
            ->recordTitleAttribute('invoice.code')
            ->columns([
                TextColumn::make('invoice.code')
                    ->weight(FontWeight::Medium),

                TextColumn::make('invoice.customer_name'),

                MoneyTextColumn::make('invoice.total')
                    ->currencyCode(fn (): string => $this->getOwnerRecord()->currency_code ?? 'USD'),

                MoneyTextColumn::make('amount')
                    ->currencyCode(fn (): string => $this->getOwnerRecord()->currency_code ?? 'USD'),

                TextColumn::make('applied_at')
                    ->dateTime('d/m/Y H:i'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('applied_at', 'desc');
    }
}
