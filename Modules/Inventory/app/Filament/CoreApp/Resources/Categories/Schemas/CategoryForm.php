<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Categories\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\CategoryResource;
use Modules\Inventory\Models\Category;

final class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::infoSection(),
                self::accountingSection(),
            ])
            ->columns(1);
    }

    private static function infoSection(): Section
    {
        return Section::make(__('Category Information'))
            ->icon(CategoryResource::getNavigationIcon())
            ->schema([
                CodeTextInput::make()
                    ->autoGenerateFromModel(
                        modelClass: Category::class,
                        prefix: Category::getCodePrefix(),
                        scope: fn () => ['company_id' => Filament::getTenant()?->getKey()],
                    )
                    ->columnSpan(3),

                NameTextInput::make()
                    ->autofocus()
                    ->columnSpan(9),

                Select::make('parent_id')
                    ->label(__('Parent Category'))
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->whereNull('parent_id')->select(['id', 'name'])
                    )
                    ->preload()
                    ->searchable()
                    ->nullable()
                    ->columnSpan(9),

                Toggle::make('is_active')
                    ->inline(false)
                    ->default(true)
                    ->columnSpan(3),

                Textarea::make('description')
                    ->rows(2)
                    ->columnSpan(12),
            ])
            ->columns(12);
    }

    private static function accountingSection(): Section
    {
        return Section::make(__('Accounting'))
            ->collapsed()
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('sales_account_id')
                            ->label(__('Sales Account'))
                            ->nullable(),

                        TextInput::make('purchase_account_id')
                            ->label(__('Purchase Account'))
                            ->nullable(),

                        TextInput::make('inventory_account_id')
                            ->label(__('Inventory Account'))
                            ->nullable(),
                    ]),
            ]);
    }
}
