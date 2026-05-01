<?php

declare(strict_types=1);

namespace Modules\System\Filament\SystemAdmin\Resources\TaxCatalogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;
use Modules\System\Enums\TaxGroupEnum;

final class TaxCatalogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Identification'))
                    ->afterHeader([
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->schema([
                        CodeTextInput::make()
                            ->unique(ignoreRecord: true)
                            ->columnSpan(3),

                        NameTextInput::make()
                            ->unique(ignoreRecord: true)
                            ->autofocus()
                            ->columnSpan(9),

                        Select::make('tax_group')
                            ->options(TaxGroupEnum::class)
                            ->required()
                            ->columnSpan(4),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(3),

                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(12)
                    ->columnSpan(6),
            ])
            ->columns(12);
    }
}
