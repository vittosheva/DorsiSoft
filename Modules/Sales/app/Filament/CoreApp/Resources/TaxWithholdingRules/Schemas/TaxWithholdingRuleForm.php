<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\TaxWithholdingRules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\System\Enums\TaxGroupEnum;

final class TaxWithholdingRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(12)->schema([
                Section::make(__('General'))
                    ->icon(Heroicon::ShieldCheck)
                    ->schema([
                        Select::make('type')
                            ->options(TaxGroupEnum::withholdingOptions())
                            ->required()
                            ->columnSpan(6),

                        TextInput::make('concept')
                            ->required()
                            ->columnSpan(6),

                        TextInput::make('percentage')
                            ->numeric()
                            ->required()
                            ->helperText(__('Percentage applied for this rule (e.g., 3.00)'))
                            ->columnSpan(4),

                        TextInput::make('account')
                            ->columnSpan(4)
                            ->placeholder(__('Optional GL account code')),

                        Toggle::make('active')
                            ->default(true)
                            ->columnSpan(4),
                    ])
                    ->columns(12),

                Section::make(__('Audit'))
                    ->schema([
                        AuditSection::make(),
                    ])
                    ->columnSpan(12),
            ])->columns(1),
        ]);
    }
}
