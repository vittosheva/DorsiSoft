<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxDefinitions\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\System\Enums\WithholdingAppliesToEnum;

final class TaxWithholdingRatesRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'withholdingRates';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('percentage')
                    ->numeric()
                    ->rule('decimal:0,2')
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->required(),

                TextInput::make('sri_code')
                    ->label(__('ATS Code'))
                    ->maxLength(10)
                    ->required(),

                TextInput::make('description')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('applies_to')
                    ->options(WithholdingAppliesToEnum::options())
                    ->in(WithholdingAppliesToEnum::values()),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Withholding rates applicable to this tax definition, specifying the percentage to retain from supplier payments depending on the transaction context. Withholding rates are determined by SRI regulations and vary based on the type of income or expense being taxed.'))
            ->heading(__('Withholding Rates'))
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('percentage')
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2).' %')
                    ->sortable()
                    ->alignment(Alignment::Right),

                TextColumn::make('sri_code')
                    ->label(__('ATS Code'))
                    ->alignment(Alignment::Center),

                TextColumn::make('description'),

                TextColumn::make('applies_to')
                    ->badge()
                    ->alignment(Alignment::Center),

                IconColumn::make('is_active')
                    ->boolean()
                    ->alignment(Alignment::Center),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('percentage');
    }
}
