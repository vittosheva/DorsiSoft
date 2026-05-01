<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\Users\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Enums\LanguageEnum;
use Modules\Core\Models\CashRegister;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\PaymentMethod;
use Modules\Core\Services\FileStoragePathService;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\Selects\EstablishmentSelect;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;
use Spatie\Permission\Models\Role;
use Tapp\FilamentTimezoneField\Forms\Components\TimezoneSelect;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use YousefAman\ModalRepeater\Column;
use YousefAman\ModalRepeater\ModalRepeater;

final class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([...self::leftColumn()])
                    ->columnSpan(9),

                Grid::make()
                    ->schema([...self::rightColumn()])
                    ->columns(1)
                    ->columnSpan(3),
            ])
            ->columns(12);
    }

    /**
     * @return array<Section>
     */
    private static function leftColumn(): array
    {
        return [
            self::basicInfoSection(),
            self::credentialsSection(),
            self::assignmentSection(),
            self::emissionPointsSection(),
        ];
    }

    /**
     * @return array<Section>
     */
    private static function rightColumn(): array
    {
        return [
            self::avatarSection(),
            self::accountStatusSection(),
            AuditSection::make(),
        ];
    }

    private static function basicInfoSection(): Section
    {
        return Section::make(__('Basic Information'))
            ->icon(Heroicon::UserCircle)
            ->description(__('Main account identity details.'))
            ->schema([
                CodeTextInput::make('code')
                    ->autoGenerateFromModel(
                        scope: fn (): array => [
                            'company_id' => Filament::getTenant()?->getKey(),
                        ],
                    )
                    ->tenantScopedUnique()
                    ->columnSpan(2),

                NameTextInput::make()
                    ->autofocus()
                    ->columnSpan(3),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->scopedUnique(ignoreRecord: true)
                    ->maxLength(100)
                    ->columnSpan(3),

                TextInput::make('phone')
                    ->tel()
                    ->maxLength(20)
                    ->columnSpan(3),
            ])
            ->columns(12)
            ->columnSpanFull();
    }

    private static function credentialsSection(): Section
    {
        return Section::make(__('Credentials'))
            ->icon(Heroicon::LockClosed)
            ->description(__('Access credentials and organizational assignment.'))
            ->schema([
                Select::make('role_name')
                    ->options(function (): array {
                        $tenantId = Filament::getTenant()?->getKey();

                        if (blank($tenantId)) {
                            return [];
                        }

                        return Cache::remember(
                            "tenant_roles.{$tenantId}",
                            900,
                            fn () => Role::query()
                                ->select(['name'])
                                ->where('company_id', $tenantId)
                                ->where('guard_name', 'web')
                                ->orderBy('name')
                                ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                                ->pluck('name', 'name')
                                ->all(),
                        );
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->dehydrated(false)
                    ->columnSpan(3),

                TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->revealable()
                    ->minLength(8)
                    ->same('password_confirmation')
                    ->validationAttribute(__('Password'))
                    ->columnSpan(2),

                TextInput::make('password_confirmation')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false)
                    ->revealable()
                    ->minLength(8)
                    ->validationAttribute(__('Password confirmation'))
                    ->columnSpan(2),
            ])
            ->columns(12)
            ->columnSpanFull();
    }

    private static function assignmentSection(): Section
    {
        return Section::make(__('Assignment'))
            ->icon(Phosphor::UserCirclePlusFill)
            ->description(__('Contact and locale settings for this user.'))
            ->schema([
                EstablishmentSelect::make('establishment_id')
                    ->disabled(false)
                    ->required(false)
                    ->columnSpan(5),

                Select::make('language')
                    ->options(LanguageEnum::class)
                    ->enum(LanguageEnum::class)
                    ->default(LanguageEnum::DEFAULT)
                    ->required()
                    ->columnSpan(3),

                TimezoneSelect::make('timezone')
                    ->columnSpan(4),
            ])
            ->columns(12)
            ->columnSpanFull();
    }

    private static function emissionPointsSection(): Section
    {
        return Section::make(__('Emission Points Assignment'))
            ->icon(Phosphor::CashRegisterFill)
            ->description(__('Assignment and configuration of charges by emission point.'))
            ->schema([
                ModalRepeater::make('userEmissionPoints')
                    ->relationship('userEmissionPoints')
                    ->hiddenLabel()
                    ->tableColumns([
                        Column::make('emission_point_id')->label(__('Emission Point')),
                        Column::make('is_default')->label(__('Default'))->boolean(),
                        Column::make('payment_method_id')->label(__('Payment Method')),
                        Column::make('cash_register_id')->label(__('Cash Register'))->boolean(),
                        Column::make('allow_mixed_payments')->label(__('Allow Mixed Payments'))->boolean(),
                        Column::make('restrict_payment_methods')->label(__('Restrict Payment Methods'))->boolean(),
                    ])
                    ->schema([
                        Select::make('emission_point_id')
                            ->options(
                                fn () => EmissionPoint::query()
                                    ->select(['id', 'name', 'code'])
                                    ->where('company_id', Filament::getTenant()->getKey())
                                    ->where('is_active', true)
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(fn ($ep) => [$ep->id => "[{$ep->code}] {$ep->name}"])
                            )
                            ->required()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->searchable()
                            ->lazy(),
                        Select::make('payment_method_id')
                            ->options(
                                fn () => PaymentMethod::query()
                                    ->where('company_id', Filament::getTenant()->getKey())
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->nullable()
                            ->lazy()
                            ->requiredIf('restrict_payment_methods', true),
                        Select::make('cash_register_id')
                            ->options(
                                fn () => CashRegister::query()
                                    ->where('company_id', Filament::getTenant()->getKey())
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->nullable(),
                        Toggle::make('allow_mixed_payments')->inline(false),
                        Toggle::make('restrict_payment_methods')->inline(false),
                        Toggle::make('require_shift')->inline(false),
                        Toggle::make('is_default')->inline(false),
                    ])
                    ->addActionLabel(__('Add Emission Point'))
                    ->addActionAlignment(Alignment::Center)
                    ->modalColumns(3)
                    ->modalWidth(Width::FourExtraLarge)
                    ->minItems(0)
                    ->maxItems(10)
                    ->defaultItems(0)
                    ->reorderable()
                    ->cloneable(false)
                    ->live()
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    private static function avatarSection(): Section
    {
        return Section::make(__('Avatar'))
            ->icon(Phosphor::ImageFill)
            ->description(__('Profile image used across the system.'))
            ->schema([
                FileUpload::make('avatar_url')
                    ->hiddenLabel()
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatioOptions([
                        '1:1',
                    ])
                    ->disk(FileStoragePathService::getDisk(FileTypeEnum::UserAvatars))
                    ->directory(fn (?Model $record) => FileStoragePathService::getPath(
                        FileTypeEnum::UserAvatars,
                        $record,
                    ))
                    ->maxSize(FileStoragePathService::getMaxSizeKb(FileTypeEnum::UserAvatars))
                    ->visibility(FileStoragePathService::getVisibility(FileTypeEnum::UserAvatars))
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    private static function accountStatusSection(): Section
    {
        return Section::make(__('Account Status'))
            ->icon(Phosphor::CheckCircleFill)
            ->description(__('Define if this user can access the panel.'))
            ->schema([
                Toggle::make('is_allowed_to_login')
                    ->label(__('Is allowed to login'))
                    ->default(true),
                Toggle::make('is_active')
                    ->label(__('Is active'))
                    ->default(true),
            ])
            ->columnSpanFull();
    }
}
