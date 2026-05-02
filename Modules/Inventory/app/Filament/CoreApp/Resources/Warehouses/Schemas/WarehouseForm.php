<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Warehouses\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Models\Establishment;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;
use Modules\Inventory\Models\Warehouse;
use ToneGabes\Filament\Icons\Enums\Phosphor;

final class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Warehouse Information'))
                    ->icon(Phosphor::Warehouse)
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                CodeTextInput::make()
                                    ->autoGenerateFromModel(
                                        modelClass: Warehouse::class,
                                        prefix: Warehouse::getCodePrefix(),
                                        scope: fn () => ['company_id' => Filament::getTenant()?->getKey()],
                                    )
                                    ->columnSpan(3),

                                NameTextInput::make()
                                    ->tenantScopedUnique()
                                    ->autofocus()
                                    ->columnSpan(9),

                                Select::make('establishment_id')
                                    ->options(fn () => Establishment::query()
                                        ->where('company_id', Filament::getTenant()?->getKey())
                                        ->where('is_active', true)
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(6),

                                Textarea::make('address')
                                    ->maxLength(255)
                                    ->columnSpan(6),

                                Toggle::make('is_default')
                                    ->columnSpan(4),

                                Toggle::make('is_active')
                                    ->default(true)
                                    ->columnSpan(3),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
