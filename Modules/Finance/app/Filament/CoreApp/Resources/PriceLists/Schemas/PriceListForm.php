<?php

declare(strict_types=1);

namespace Modules\Finance\Filament\CoreApp\Resources\PriceLists\Schemas;

use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Support\Forms\Selects\CurrencyCodeSelect;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;
use Modules\Finance\Filament\CoreApp\Resources\PriceLists\PriceListResource;
use Modules\Finance\Models\PriceList;

final class PriceListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Price List Information'))
                    ->icon(PriceListResource::getNavigationIcon())
                    ->schema([
                        Grid::make(12)->schema([
                            CodeTextInput::make()
                                ->autoGenerateFromModel(
                                    modelClass: PriceList::class,
                                    prefix: PriceList::getCodePrefix(),
                                    scope: fn () => ['company_id' => Filament::getTenant()?->getKey()],
                                )
                                ->columnSpan(3),

                            NameTextInput::make()
                                ->tenantScopedUnique()
                                ->autofocus()
                                ->columnSpan(6),

                            CurrencyCodeSelect::make('currency_code')
                                ->optionsForCodes(['USD', 'EUR'])
                                ->required()
                                ->default('USD')
                                ->columnSpan(3),

                            DatePicker::make('start_date')
                                ->label(__('Valid From'))
                                ->nullable()
                                ->columnSpan(3),

                            DatePicker::make('end_date')
                                ->label(__('Valid Until'))
                                ->nullable()
                                ->after('start_date')
                                ->columnSpan(3),

                            Toggle::make('is_default')
                                ->rules([
                                    fn (?PriceList $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                        if (! $value) {
                                            return;
                                        }

                                        $companyId = Filament::getTenant()?->getKey();

                                        if (! PriceList::hasDefaultForCompany($companyId, $record?->getKey())) {
                                            return;
                                        }

                                        $fail(__('Only one default price list is allowed per company.'));
                                    },
                                ])
                                ->inline(false)
                                ->columnSpan(3),

                            Toggle::make('is_active')
                                ->inline(false)
                                ->default(true)
                                ->columnSpan(3),

                            RichEditor::make('description')
                                ->columnSpan(12),
                        ]),
                    ]),
            ])
            ->columns(1);
    }
}
