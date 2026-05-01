<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\DocumentTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Modules\Inventory\Enums\MovementTypeEnum;

final class DocumentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Document Type'))
                    ->icon(Heroicon::DocumentDuplicate)
                    ->description(__('Define the properties of the inventory document type.'))
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(3),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(5),

                        Select::make('movement_type')
                            ->options(MovementTypeEnum::class)
                            ->required()
                            ->columnSpan(4),

                        Textarea::make('notes')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Toggle::make('affects_inventory')
                            ->helperText(__('When enabled, movements of this type update the actual stock balance in the warehouse.'))
                            ->default(true)
                            ->columnSpan(4),

                        Toggle::make('requires_source_document')
                            ->helperText(__('When enabled, movements of this type must originate from another document (e.g. an invoice or purchase). They cannot be created manually — the system generates them automatically.'))
                            ->default(false)
                            ->columnSpan(4),

                        Toggle::make('is_active')
                            ->default(true)
                            ->columnSpan(4),

                        Callout::make()
                            ->description(__('Document types with "Requires source document" enabled (e.g. Sale, Purchase, Purchase Return) are generated automatically when the originating document is authorized. Document types without this flag (e.g. Entry Adjustment, Exit Adjustment, Transfer) can be used freely from the "New Movement" form.'))
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->iconColor(Color::Blue)
                            ->info()
                            ->columnSpanFull(),
                    ])
                    ->columns(12),
            ])
            ->columns(1);
    }
}
