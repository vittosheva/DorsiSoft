<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Resources\EstablishmentResource\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Support\Forms\TextInputs\ThreeDigitCodeTextInput;

final class EstablishmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Establishment data'))
                    ->schema([
                        ThreeDigitCodeTextInput::make('code')
                            ->autofocus()
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),

                        Toggle::make('is_active')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(2),

                        Textarea::make('address')
                            ->rows(3)
                            ->maxLength(255)
                            ->columnSpan(6),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(30)
                            ->columnSpan(4),
                    ])
                    ->columns(10)
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
