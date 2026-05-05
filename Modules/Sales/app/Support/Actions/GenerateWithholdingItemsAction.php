<?php

declare(strict_types=1);

namespace Modules\Sales\Support\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Operation;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Modules\Sales\Services\WithholdingSuggestionService;
use Modules\System\Enums\WithholdingAppliesToEnum;

final class GenerateWithholdingItemsAction
{
    public static function make(string $name = 'generateWithholdingItems'): Action
    {
        return Action::make($name)
            ->label(__('Generate automatically'))
            ->icon(Heroicon::Sparkles)
            ->color('info')
            ->modalHeading(__('Generate Withholding Lines'))
            ->modalDescription(__('Enter the source document amounts and the system will suggest the applicable withholding lines.'))
            ->modalWidth(Width::Large)
            ->modalSubmitActionLabel(__('Generate lines'))
            ->schema([
                TextInput::make('subtotal_base')
                    ->label(__('Subtotal (tax base without VAT)'))
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('$')
                    ->placeholder('0.00'),

                TextInput::make('iva_amount')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('$')
                    ->placeholder('0.00'),

                Select::make('applies_to')
                    ->label(__('Purchase applies to'))
                    ->options(WithholdingAppliesToEnum::class)
                    ->required()
                    ->default(WithholdingAppliesToEnum::Servicio->value),
            ])
            ->action(function (array $data, Set $set): void {
                $suggestions = app(WithholdingSuggestionService::class)->suggestItems(
                    subtotal: (float) ($data['subtotal_base'] ?? 0),
                    ivaAmount: (float) ($data['iva_amount'] ?? 0),
                    appliesTo: $data['applies_to'],
                );

                if (empty($suggestions)) {
                    return;
                }

                $set('items', $suggestions);
            })
            ->visible(fn ($operation) => in_array($operation, [Operation::Create->value, Operation::Edit->value]));
    }
}
