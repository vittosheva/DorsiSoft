<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\BusinessPartners\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Core\Support\Forms\Selects\IdentificationTypeSelect;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\IdentificationNumberTextInput;
use Modules\People\Models\BusinessPartner;

final class CustomerMinimalCreateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::basicInfoSection(),
                self::statusSection(),
            ])
            ->columns(2);
    }

    private static function basicInfoSection(): Section
    {
        return Section::make(__('Basic Information'))
            ->description(__('Main identity and classification data.'))
            ->schema([
                CodeTextInput::make('code')
                    ->autoGenerateFromModel(
                        scope: fn () => [
                            'company_id' => Filament::getTenant()?->getKey(),
                        ],
                    )
                    ->columnSpan(1),

                IdentificationTypeSelect::make('identification_type')
                    ->columnSpan(1),

                IdentificationNumberTextInput::make('identification_number')
                    ->uniqueAmong(BusinessPartner::class)
                    ->autofocus()
                    ->columnSpan(1),

                TextInput::make('legal_name')
                    ->required()
                    ->maxLength(150)
                    ->visibleJs(<<<'JS'
                        $get('identification_number')
                    JS)
                    ->columnStart(1)
                    ->columnSpanFull(),

                TextInput::make('email')
                    ->email()
                    ->maxLength(150)
                    ->columnSpan(1),

                TextInput::make('phone')
                    ->maxLength(30)
                    ->columnSpan(1),
            ])
            ->columns(3)
            ->columnSpanFull();
    }

    private static function statusSection(): Section
    {
        return Section::make(__('Status'))
            ->description(__('Availability for operations.'))
            ->schema([
                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
            ])
            ->columnSpan(1);
    }
}
