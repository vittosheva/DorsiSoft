<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Brands\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Services\FileStoragePathService;
use Modules\Core\Support\Tables\Columns\CreatedAtTextColumn;
use Modules\Core\Support\Tables\Columns\CreatedByTextColumn;
use Modules\Core\Support\Tables\Columns\IsActiveColumn;
use Modules\Core\Support\Tables\Filters\IsActiveFilter;

final class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->description(__('Brands associated with this company\'s products. Each brand can include a logo for display in catalogues and documents. Brands help classify products by manufacturer or commercial line and can be used as filters in product searches.'))
            ->columns([
                Stack::make([
                    TextColumn::make('name')
                        ->weight(FontWeight::SemiBold)
                        ->searchable()
                        ->sortable(),

                    ImageColumn::make('logo_url')
                        ->circular()
                        ->defaultImageUrl(null)
                        ->disk(fn () => FileStoragePathService::getDisk(FileTypeEnum::BrandLogos))
                        ->imageWidth('100%')
                        ->alignment(Alignment::Center),

                    IsActiveColumn::make('is_active')
                        ->tooltip(null)
                        ->action(null),
                ]),

                CreatedByTextColumn::make(),

                CreatedAtTextColumn::make(),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 5,
            ])
            ->filters([
                IsActiveFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
