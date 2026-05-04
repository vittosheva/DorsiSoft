<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\JournalEntryResource;
use Modules\Accounting\Models\FiscalPeriod;

final class JournalEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Journal Entry'))
                    ->icon(JournalEntryResource::getNavigationIcon())
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Select::make('fiscal_period_id')
                                    ->options(fn () => FiscalPeriod::query()
                                        ->open()
                                        ->where('start_date', '<=', now())
                                        ->orderByDesc('year')
                                        ->orderByDesc('month')
                                        ->get()
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->columnSpan(4),

                                DatePicker::make('entry_date')
                                    ->required()
                                    ->default(today())
                                    ->columnSpan(4),

                                TextInput::make('reference')
                                    ->readOnly()
                                    ->placeholder(__('Auto-generated'))
                                    ->columnSpan(4),

                                TextInput::make('description')
                                    ->required()
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
