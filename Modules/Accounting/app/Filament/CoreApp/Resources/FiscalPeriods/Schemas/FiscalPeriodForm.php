<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\FiscalPeriods\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Modules\Accounting\Enums\FiscalPeriodStatusEnum;

final class FiscalPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('year')
                    ->options(fn () => range(now()->year - 2, now()->year + 2))
                    ->required()
                    ->live(),
                Select::make('month')
                    ->options([
                        1 => __('January'),
                        2 => __('February'),
                        3 => __('March'),
                        4 => __('April'),
                        5 => __('May'),
                        6 => __('June'),
                        7 => __('July'),
                        8 => __('August'),
                        9 => __('September'),
                        10 => __('October'),
                        11 => __('November'),
                        12 => __('December'),
                    ])
                    ->required()
                    ->live(),
                TextInput::make('name')
                    ->default(fn ($get) => ($get('month') ? [1 => __('January'), 2 => __('February'), 3 => __('March'), 4 => __('April'), 5 => __('May'), 6 => __('June'), 7 => __('July'), 8 => __('August'), 9 => __('September'), 10 => __('October'), 11 => __('November'), 12 => __('December')][$get('month')] : '').' '.$get('year'))
                    ->readOnly(),
                DatePicker::make('start_date')
                    ->default(fn ($get) => $get('year') && $get('month') ? Carbon::create($get('year'), $get('month'), 1)->toDateString() : null),
                DatePicker::make('end_date')
                    ->default(fn ($get) => $get('year') && $get('month') ? Carbon::create($get('year'), $get('month'), 1)->endOfMonth()->toDateString() : null),
                Select::make('status')
                    ->options(FiscalPeriodStatusEnum::class)
                    ->default(FiscalPeriodStatusEnum::OPEN),
            ])
            ->columns(3);
    }
}
