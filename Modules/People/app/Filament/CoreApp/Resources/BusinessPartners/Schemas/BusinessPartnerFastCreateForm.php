<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\BusinessPartners\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Support\Forms\Selects\IdentificationTypeSelect;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\IdentificationNumberTextInput;
use Modules\People\Models\BusinessPartner;
use Modules\People\Support\Actions\DismissDuplicatePartnerCalloutAction;
use Modules\People\Support\Actions\GoToExistingBusinessPartnerAction;

final class BusinessPartnerFastCreateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                self::basicInfoSection(),
                self::rolesSection(),
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
                    ->sriAllowedFields(['legal_name'])
                    ->uniqueAmong(BusinessPartner::class)
                    ->autofocus()
                    ->columnSpan(1),

                TextInput::make('legal_name')
                    ->required()
                    ->maxLength(150)
                    ->columnSpanFull(),

                Callout::make(fn (Get $get): string => $get('_duplicate_partner_name') ?? '')
                    ->description(__('An entity with this identification already exists. You may want to edit it instead of creating a duplicate.'))
                    ->warning()
                    ->icon(Heroicon::ExclamationTriangle)
                    ->actions([
                        GoToExistingBusinessPartnerAction::make('go_to_existing_partner'),
                    ])
                    ->controlActions([
                        DismissDuplicatePartnerCalloutAction::make('dismiss'),
                    ])
                    ->hiddenJs(
                        <<<'JS'
                            $get('hideCallout') || !$get('_duplicate_partner_id')
                        JS
                    )
                    ->columnSpanFull(),
            ])
            ->columns(3)
            ->columnSpanFull();
    }

    private static function rolesSection(): Section
    {
        return Section::make(__('Roles'))
            ->description(__('Assign roles to the entity.'))
            ->schema([
                Select::make('roles')
                    ->hiddenLabel()
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->select(['core_partner_roles.id', 'core_partner_roles.name'])
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->limit(config('dorsi.filament.select_filter_options_limit', 50)),
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => __($record->name))
                    ->multiple()
                    ->preload()
                    ->required(),
            ])
            ->columnSpanFull();
    }

    private static function statusSection(): Section
    {
        return Section::make(__('Status'))
            ->description(__('Availability for operations.'))
            ->schema([
                Toggle::make('is_active')
                    ->default(true),
            ])
            ->columnSpan(1);
    }
}
