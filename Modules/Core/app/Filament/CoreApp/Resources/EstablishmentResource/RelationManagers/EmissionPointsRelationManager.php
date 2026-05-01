<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Resources\EstablishmentResource\RelationManagers;

use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Support\Forms\TextInputs\ThreeDigitCodeTextInput;
use Modules\Core\Support\RelationManagers\BaseRelationManager;
use Modules\Core\Support\Tables\Columns\CodeTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Core\Support\Tables\Columns\IsDefaultIconColumn;

final class EmissionPointsRelationManager extends BaseRelationManager
{
    protected static string $relationship = 'emissionPoints';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Emission Points');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ThreeDigitCodeTextInput::make('code')
                    ->required()
                    ->scopedUnique()
                    ->columnSpan(3),

                TextInput::make('name')
                    ->maxLength(255)
                    ->columnSpan(9),

                Toggle::make('is_default')
                    ->inline(false)
                    ->default(false)
                    ->rules([
                        fn (?Model $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                            if ($value) {
                                $exists = EmissionPoint::query()
                                    ->where('is_default', true)
                                    ->when($record, fn ($query) => $query->whereNot('id', $record->getKey()))
                                    ->exists();
                                if ($exists) {
                                    $fail(__('Only one emission point can be set as default.'));
                                }
                            }
                        },
                    ])
                    ->columnSpan(3),

                Toggle::make('is_active')
                    ->inline(false)
                    ->default(true)
                    ->columnSpan(2),
            ])
            ->columns(12);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(__('Emission points configured within this establishment. Each emission point represents a unique issuing location assigned a sequential number by the SRI. Documents such as invoices, credit notes, and withholdings are issued from a specific emission point.'))
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('creator:id,name,avatar_url'))
            ->modelLabel(__('Emission Point'))
            ->pluralModelLabel(__('Emission Points'))
            ->recordTitleAttribute('code')
            ->columns([
                CodeTextColumn::make('code')
                    ->badge()
                    ->alignment(Alignment::Center),

                TextColumn::make('name')
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->placeholder('—'),

                IsDefaultIconColumn::make('is_default'),

                IsActiveColumn::make('is_active'),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
