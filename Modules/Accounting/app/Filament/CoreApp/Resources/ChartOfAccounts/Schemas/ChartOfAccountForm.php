<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Accounting\Enums\AccountNatureEnum;
use Modules\Accounting\Enums\AccountTypeEnum;
use Modules\Accounting\Filament\CoreApp\Resources\ChartOfAccounts\ChartOfAccountResource;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;

final class ChartOfAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Account details'))
                    ->icon(ChartOfAccountResource::getNavigationIcon())
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('Account code'))
                                    ->required()
                                    ->maxLength(30)
                                    ->placeholder('1.1.01.001')
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(3),

                                NameTextInput::make()
                                    ->columnSpan(9),

                                Select::make('parent_id')
                                    ->label(__('Parent account'))
                                    ->options(fn () => ChartOfAccount::query()
                                        ->active()
                                        ->orderBy('code')
                                        ->pluck('name', 'id')
                                        ->map(fn ($name, $id) => $name))
                                    ->searchable()
                                    ->nullable()
                                    ->columnSpan(6),

                                TextInput::make('sri_code')
                                    ->maxLength(20)
                                    ->nullable()
                                    ->columnSpan(3),

                                TextInput::make('level')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->columnSpan(3),

                                Select::make('type')
                                    ->label(__('Account type'))
                                    ->options(AccountTypeEnum::options())
                                    ->required()
                                    ->columnSpan(4),

                                Select::make('nature')
                                    ->label(__('Normal balance'))
                                    ->options(AccountNatureEnum::options())
                                    ->required()
                                    ->columnSpan(4),

                                Toggle::make('is_control')
                                    ->label(__('Control account (no direct entries)'))
                                    ->inline(false)
                                    ->columnSpan(5)
                                    ->columnStart(1),

                                Toggle::make('allows_entries')
                                    ->inline(false)
                                    ->default(true)
                                    ->columnSpan(3),

                                Toggle::make('is_active')
                                    ->inline(false)
                                    ->default(true)
                                    ->columnSpan(3),

                                Textarea::make('notes')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
