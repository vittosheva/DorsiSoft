<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Units\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;
use Modules\Inventory\Filament\CoreApp\Resources\Units\UnitResource;
use Modules\Inventory\Models\Unit;

final class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Unit Information'))
                    ->icon(UnitResource::getNavigationIcon())
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                CodeTextInput::make()
                                    ->autoGenerateFromModel(
                                        modelClass: Unit::class,
                                        prefix: Unit::getCodePrefix(),
                                        scope: fn () => ['company_id' => Filament::getTenant()?->getKey()],
                                    )
                                    ->columnSpan(3),

                                NameTextInput::make()
                                    ->tenantScopedUnique()
                                    ->autofocus()
                                    ->columnSpan(6),

                                TextInput::make('symbol')
                                    ->maxLength(10)
                                    ->placeholder(__('e.g. kg, m, L'))
                                    ->columnSpan(3),

                                Toggle::make('is_active')
                                    ->default(true)
                                    ->columnSpan(3),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
