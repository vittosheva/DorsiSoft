<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Forms\Sections;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

final class AdditionalInfoRepeaterSection
{
    public static function make(): Section
    {
        return Section::make(__('Additional Information'))
            ->icon(Heroicon::OutlinedInformationCircle)
            ->schema([
                EmptyState::make(__('No additional information has been entered yet.'))
                    ->description(__('Add one or more fields if you need to include extra information in the document.'))
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->visible(fn (Get $get): bool => blank($get('additional_info')))
                    ->visibleJs(<<<'JS'
                        !(($get('additional_info') || []).length)
                    JS)
                    ->compact()
                    ->columnSpanFull(),

                Repeater::make('additional_info')
                    ->table([
                        TableColumn::make(__('Field'))
                            ->markAsRequired()
                            ->width('40%'),
                        TableColumn::make(__('Value'))
                            ->markAsRequired()
                            ->width('60%'),
                    ])
                    ->schema([
                        TextInput::make('key')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(4),

                        TextInput::make('value')
                            ->required()
                            ->maxLength(300)
                            ->columnSpan(8),
                    ])
                    ->compact()
                    ->hiddenLabel()
                    ->defaultItems(0)
                    ->addActionLabel(__('Add Field')),
            ])
            ->collapsible();
    }
}
