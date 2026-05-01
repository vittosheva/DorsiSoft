<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Services\FileStoragePathService;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\BrandResource;

final class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Brand Information'))
                    ->icon(BrandResource::getNavigationIcon())
                    ->schema([
                        NameTextInput::make()
                            ->tenantScopedUnique()
                            ->autofocus()
                            ->columnSpan(9),

                        Toggle::make('is_active')
                            ->inline(false)
                            ->default(true)
                            ->columnSpan(3),

                        RichEditor::make('description')
                            ->columnSpan(12),

                        FileUpload::make('logo_url')
                            ->image()
                            ->disk(fn () => FileStoragePathService::getDisk(FileTypeEnum::BrandLogos))
                            ->directory(fn ($record) => FileStoragePathService::getPath(FileTypeEnum::BrandLogos, $record))
                            ->visibility(fn () => FileStoragePathService::getVisibility(FileTypeEnum::BrandLogos))
                            ->acceptedFileTypes(fn () => FileStoragePathService::getAcceptedTypes(FileTypeEnum::BrandLogos))
                            ->maxSize(fn () => FileStoragePathService::getMaxSizeKb(FileTypeEnum::BrandLogos))
                            ->columnSpan(12),
                    ])
                    ->columns(12)
                    ->columnSpanFull(),
            ]);
    }
}
