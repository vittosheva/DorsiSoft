<?php

declare(strict_types=1);

namespace Modules\People\Filament\CoreApp\Resources\BusinessPartners\Schemas;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Operation;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Support\Actions\CreateRelatedDocumentAction;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\Selects\IdentificationTypeSelect;
use Modules\Core\Support\Forms\Selects\RatingSelect;
use Modules\Core\Support\Forms\Textareas\NotesTextarea;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\IdentificationNumberTextInput;
use Modules\Core\Support\Forms\TextInputs\PaymentTermsDaysTextInput;
use Modules\Core\Support\Forms\TextInputs\PhoneTextInput;
use Modules\Core\Support\Forms\Toggles\IsDefaultToggle;
use Modules\Finance\Filament\CoreApp\Resources\Collections\CollectionResource;
use Modules\People\Enums\AddressTypeEnum;
use Modules\People\Enums\BankAccountTypeEnum;
use Modules\People\Models\BusinessPartner;
use Modules\People\Services\PartnerRoleLookup;
use Modules\People\Support\Actions\DismissDuplicatePartnerCalloutAction;
use Modules\People\Support\Actions\GoToExistingBusinessPartnerAction;
use Modules\People\Support\Forms\Selects\SellerUserSelect;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\QuotationResource;
use Modules\Sales\Filament\CoreApp\Resources\SalesOrders\SalesOrderResource;
use ToneGabes\Filament\Icons\Enums\Phosphor;

final class BusinessPartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        self::basicInfoSection(),
                        self::preHeader(),
                        self::mainTabs(),
                    ])
                    ->columns(9)
                    ->columnSpan(9),

                Grid::make()
                    ->schema([
                        ...self::rightColumn(),
                    ])
                    ->columns(1)
                    ->columnSpan(3),
            ])
            ->columns(12);
    }

    private static function basicInfoSection(): Section
    {
        return Section::make(__('Basic Information'))
            ->icon(Heroicon::Identification)
            ->description(__('Main identity and classification data.'))
            ->schema([
                CodeTextInput::make('code')
                    ->autoGenerateFromModel(
                        scope: fn () => [
                            'company_id' => Filament::getTenant()?->getKey(),
                        ],
                    )
                    ->columnSpan(2),

                IdentificationTypeSelect::make('identification_type')
                    ->columnSpan(3),

                IdentificationNumberTextInput::make('identification_number')
                    ->sriAllowedFields([
                        'legal_name',
                        'trade_name',
                        'business_activity',
                        'tax_regime',
                        'contributor_status',
                        'taxpayer_type',
                        'contributor_category',
                        'is_accounting_required',
                        'is_special_taxpayer',
                        'is_withholding_agent',
                        'is_shell_company',
                        'has_nonexistent_transactions',
                        'started_activities_at',
                        'ceased_activities_at',
                        'restarted_activities_at',
                        'sri_updated_at',
                        'legal_representatives',
                        'suspension_cancellation_reason',
                    ])
                    ->uniqueAmong(BusinessPartner::class)
                    ->columnSpan(3),

                TextInput::make('legal_name')
                    ->required()
                    ->maxLength(150)
                    ->columnSpan(6)
                    ->columnStart(1)
                    ->visibleJs(<<<'JS'
                        $get('identification_number')
                    JS),

                TextInput::make('trade_name')
                    ->maxLength(150)
                    ->columnSpan(6)
                    ->visibleJs(self::roleVisibleJs(['customer', 'supplier', 'carrier'])),

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
            ->columns(12)
            ->columnSpanFull();
    }

    private static function mainTabs(): Tabs
    {
        return Tabs::make()
            ->key('business-partner-tabs')
            ->schema([
                Tab::make(__('Contact Information'))
                    ->schema([
                        self::tabContactSection(),
                        self::tabAddressesSection(),
                    ]),
                Tab::make(__('Customer Details'))
                    ->schema([
                        self::tabCustomerSection(),
                    ])
                    ->visibleJs(self::roleVisibleJs('customer')),
                Tab::make(__('Supplier Details'))
                    ->schema([
                        self::tabSupplierSection(),
                    ])
                    ->visibleJs(self::roleVisibleJs('supplier')),
                Tab::make(__('Carrier Details'))
                    ->schema([
                        self::tabCarrierSection(),
                        self::tabCarrierVehiclesSection(),
                    ])
                    ->visibleJs(self::roleVisibleJs('carrier')),
                Tab::make(__('Bank Accounts'))
                    ->schema([
                        self::tabBankAccountsSection(),
                    ]),
            ])
            ->columnSpanFull();
    }

    private static function preHeader(): Group
    {
        return Group::make([
            EmptyState::make(__('Quotation'))
                ->description(fn (?Model $record) => __('Get started by creating a new quotation :businessPartner', ['businessPartner' => $record?->legal_name]))
                ->icon(QuotationResource::getNavigationIcon())
                ->footer([
                    CreateRelatedDocumentAction::make('create_quotation')
                        ->resourceClass(QuotationResource::class),
                ])
                ->grow(false)
                ->columnSpan(3),
            EmptyState::make(__('Sales order'))
                ->description(fn (?Model $record) => __('Get started by creating a new sales order :businessPartner', ['businessPartner' => $record?->legal_name]))
                ->icon(SalesOrderResource::getNavigationIcon())
                ->footer([
                    CreateRelatedDocumentAction::make('create_sales_order')
                        ->resourceClass(SalesOrderResource::class),
                ])
                ->grow(false)
                ->columnSpan(3),
            EmptyState::make(__('Invoice'))
                ->description(fn (?Model $record) => __('Get started by creating a new invoice :businessPartner', ['businessPartner' => $record?->legal_name]))
                ->icon(InvoiceResource::getNavigationIcon())
                ->footer([
                    CreateRelatedDocumentAction::make('create_invoice')
                        ->resourceClass(InvoiceResource::class),
                ])
                ->columnSpan(3),
            EmptyState::make(__('Collection'))
                ->description(fn (?Model $record) => __('Get started by creating a new collection :businessPartner', ['businessPartner' => $record?->legal_name]))
                ->icon(CollectionResource::getNavigationIcon())
                ->footer([
                    CreateRelatedDocumentAction::make('create_collection')
                        ->resourceClass(CollectionResource::class),
                ])
                ->columnSpan(3),
        ])
            ->hiddenOn([
                Operation::Create,
                Operation::View,
            ])
            ->columns(12)
            ->columnSpanFull();
    }

    private static function tabContactSection(): Section
    {
        return Section::make(__('Contact Information'))
            ->icon(Phosphor::UserCircleGearFill)
            ->description(__('Communication and fiscal address data.'))
            ->schema([
                Repeater::make('email')
                    ->simple(
                        TextInput::make('email')
                            ->email()
                            ->maxLength(150)
                            ->prefixIcon(Heroicon::Envelope)
                    )
                    ->minItems(0)
                    ->maxItems(5)
                    ->columnSpan(5),

                PhoneTextInput::make('phone')
                    ->columnSpan(3),

                PhoneTextInput::make('mobile')
                    ->columnSpan(3),

                Textarea::make('tax_address')
                    ->afterLabel(fn () => [
                        Action::make('showAddresses')
                            ->label(__('Show more addresses'))
                            ->icon(Heroicon::ChevronDown)
                            ->color(Color::Gray)
                            ->size(Size::ExtraSmall)
                            ->alpineClickHandler('$set(\'show_addresses\', true)')
                            ->extraAttributes(['x-show' => '!$get(\'show_addresses\')']),
                        Action::make('hideAddresses')
                            ->label(__('Hide addresses'))
                            ->icon(Heroicon::ChevronUp)
                            ->color(Color::Gray)
                            ->size(Size::ExtraSmall)
                            ->alpineClickHandler('$set(\'show_addresses\', false)')
                            ->extraAttributes(['x-show' => '$get(\'show_addresses\')', 'style' => 'display: none']),
                    ])
                    ->rows(2)
                    ->columnSpan(6),
            ])
            ->columns(12)
            ->columnSpanFull();
    }

    private static function tabCustomerSection(): Section
    {
        return Section::make(__('Customer Details'))
            ->icon(Phosphor::UserCircleGearFill)
            ->description(__('Credit, payment, and commercial conditions.'))
            ->relationship('customerDetail')
            ->schema([
                TextInput::make('credit_limit')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(3),

                TextInput::make('credit_balance')
                    ->numeric()
                    ->disabled()
                    ->columnSpan(3),

                PaymentTermsDaysTextInput::make('payment_terms_days')
                    ->columnSpan(3),

                TextInput::make('discount_percentage')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->default(0)
                    ->columnSpan(3),

                RatingSelect::make('rating')
                    ->columnSpan(3),

                Toggle::make('tax_exempt')
                    ->inline(false)
                    ->default(false)
                    ->columnSpan(3),

                SellerUserSelect::make('seller_id')
                    ->withSellerSnapshot()
                    ->columnSpan(6),

                TextInput::make('seller_name')
                    ->hidden()
                    ->dehydrated(),

                NotesTextarea::make('notes')
                    ->columnSpan(6),
            ])
            ->columns(12)
            ->columnSpanFull();
    }

    private static function tabSupplierSection(): Section
    {
        return Section::make(__('Supplier Details'))
            ->icon(Phosphor::UserCircleGearFill)
            ->description(__('Payment terms and supply conditions.'))
            ->relationship('supplierDetail')
            ->schema([
                PaymentTermsDaysTextInput::make('payment_terms_days')
                    ->columnSpan(3),

                RatingSelect::make('rating')
                    ->columnSpan(3),

                Toggle::make('tax_withholding_applicable')
                    ->inline(false)
                    ->default(false)
                    ->columnSpan(3),

                NotesTextarea::make('notes')
                    ->columnSpan(6),
            ])
            ->columns(12)
            ->columnSpanFull();
    }

    private static function tabCarrierSection(): Section
    {
        return Section::make(__('Carrier Details'))
            ->icon(Phosphor::UserCircleGearFill)
            ->description(__('Transport authorization and insurance data.'))
            ->relationship('carrierDetail')
            ->schema([
                TextInput::make('transport_authorization')
                    ->maxLength(100)
                    ->columnSpan(4),

                DatePicker::make('authorization_expiry_date')
                    ->columnSpan(3),

                TextInput::make('soat_number')
                    ->maxLength(50)
                    ->columnSpan(2),

                DatePicker::make('soat_expiry_date')
                    ->columnSpan(3),

                TextInput::make('cargo_insurance_number')
                    ->maxLength(50)
                    ->columnSpan(4),

                DatePicker::make('cargo_insurance_expiry_date')
                    ->columnSpan(3),

                TextInput::make('insurance_company')
                    ->maxLength(200)
                    ->columnSpan(5),
                TextInput::make('insurance_coverage_amount')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(3),

                RatingSelect::make('rating')
                    ->columnSpan(3),

                NotesTextarea::make('notes')
                    ->columnSpan(6),
            ])
            ->columns(12)
            ->columnSpanFull();
    }

    private static function tabCarrierVehiclesSection(): Section
    {
        return Section::make(__('Vehicles'))
            ->icon(Phosphor::TruckFill)
            ->description(__('Drivers and vehicles assigned to this carrier.'))
            ->schema([
                Repeater::make('carrierVehicles')
                    ->label(__('Vehicles'))
                    ->relationship(modifyQueryUsing: fn ($query) => $query->orderBy('vehicle_plate'))
                    ->hiddenLabel()
                    ->schema([
                        TextInput::make('driver_name')
                            ->required()
                            ->maxLength(200)
                            ->columnSpan(4),

                        TextInput::make('driver_identification')
                            ->required()
                            ->maxLength(20)
                            ->columnSpan(3),

                        TextInput::make('driver_license')
                            ->maxLength(50)
                            ->columnSpan(3),

                        TextInput::make('driver_license_type')
                            ->maxLength(10)
                            ->columnSpan(2),

                        DatePicker::make('driver_license_expiry_date')
                            ->columnSpan(3),

                        TextInput::make('vehicle_plate')
                            ->required()
                            ->maxLength(20)
                            ->columnSpan(3),

                        TextInput::make('vehicle_type')
                            ->maxLength(50)
                            ->columnSpan(3),

                        TextInput::make('vehicle_brand')
                            ->maxLength(100)
                            ->columnSpan(3),

                        TextInput::make('vehicle_model')
                            ->maxLength(100)
                            ->columnSpan(3),

                        TextInput::make('vehicle_year')
                            ->integer()
                            ->minValue(1900)
                            ->maxValue(2100)
                            ->columnSpan(2),

                        TextInput::make('vehicle_capacity_tons')
                            ->numeric()
                            ->suffix('ton')
                            ->columnSpan(3),

                        TextInput::make('vehicle_capacity_m3')
                            ->numeric()
                            ->suffix('m³')
                            ->columnSpan(3),

                        Toggle::make('is_active')
                            ->default(true)
                            ->columnSpan(2),
                    ])
                    ->columns(12)
                    ->defaultItems(0)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => ($state['vehicle_plate'] ?? '').' - '.($state['driver_name'] ?? ''))
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    private static function tabAddressesSection(): Section
    {
        return Section::make(__('Addresses'))
            ->icon(Phosphor::AddressBookFill)
            ->description(__('Billing, shipping, and branch addresses.'))
            ->schema([
                Repeater::make('addresses')
                    ->relationship(modifyQueryUsing: fn ($query) => $query->orderByDesc('is_default')->orderBy('type'))
                    ->hiddenLabel()
                    ->schema([
                        Select::make('type')
                            ->options(AddressTypeEnum::options())
                            ->required()
                            ->columnSpan(3),

                        Textarea::make('address')
                            ->required()
                            ->rows(2)
                            ->columnSpan(9),

                        TextInput::make('reference')
                            ->maxLength(200)
                            ->columnSpan(4),

                        TextInput::make('postal_code')
                            ->maxLength(10)
                            ->columnSpan(2),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->columnSpan(3),

                        IsDefaultToggle::make()
                            ->live()
                            ->afterStateUpdated(fn (bool $state, Set $set) => $state ? $set('is_active', true) : null)
                            ->columnSpan(3),

                        Toggle::make('is_active')
                            ->default(true)
                            ->disabled(fn (Get $get): bool => (bool) $get('is_default'))
                            ->dehydrated()
                            ->columnSpan(3),
                    ])
                    ->columns(12)
                    ->defaultItems(0)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => ($state['type'] ?? '').' - '.mb_substr($state['address'] ?? '', 0, 50))
                    ->columnSpanFull(),
            ])
            ->visibleJs(
                <<<'JS'
                (() => {
                    const show = $get('show_addresses') ?? false;
                    const addresses = $get('addresses') ?? [];
                    return show || (Array.isArray(addresses) && addresses.length > 0);
                })()
                JS
            )
            ->collapsible()
            ->columnSpanFull();
    }

    private static function tabBankAccountsSection(): Section
    {
        return Section::make(__('Bank accounts'))
            ->icon(Phosphor::PiggyBankFill)
            ->description(__('Bank accounts for payments and collections.'))
            ->schema([
                Repeater::make('bankAccounts')
                    ->relationship(modifyQueryUsing: fn ($query) => $query->orderByDesc('is_default')->orderBy('bank_name'))
                    ->hiddenLabel()
                    ->schema([
                        TextInput::make('bank_name')
                            ->required()
                            ->maxLength(100)
                            ->columnSpan(4),

                        Select::make('account_type')
                            ->options(BankAccountTypeEnum::options())
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('account_number')
                            ->required()
                            ->maxLength(50)
                            ->columnSpan(5),

                        TextInput::make('account_holder')
                            ->maxLength(200)
                            ->columnSpan(5),

                        TextInput::make('identification')
                            ->maxLength(20)
                            ->columnSpan(2),

                        TextInput::make('swift_code')
                            ->maxLength(20)
                            ->columnSpan(2),

                        IsDefaultToggle::make()
                            ->live()
                            ->afterStateUpdated(fn (bool $state, Set $set) => $state ? $set('is_active', true) : null)
                            ->columnSpan(3),

                        Toggle::make('is_active')
                            ->default(true)
                            ->disabled(fn (Get $get): bool => (bool) $get('is_default'))
                            ->dehydrated()
                            ->columnSpan(3),
                    ])
                    ->columns(12)
                    ->defaultItems(0)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => ($state['bank_name'] ?? '').' - '.($state['account_number'] ?? ''))
                    ->columnSpanFull(),
            ])
            ->collapsible()
            ->columnSpanFull();
    }

    private static function rightColumn(): array
    {
        return [
            Section::make(__('Roles'))
                ->icon(Phosphor::ShieldStarFill)
                ->description(__('Assign roles to the entity.'))
                ->schema([
                    Select::make('roles')
                        ->hiddenLabel()
                        ->relationship(
                            'roles',
                            'code',
                            fn ($query) => $query
                                ->select(['core_partner_roles.id', 'core_partner_roles.code'])
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->code->displayName())
                        ->multiple()
                        ->preload()
                        ->live()
                        ->required(),
                ])
                ->columnSpanFull(),

            Section::make(__('Status'))
                ->icon(Phosphor::CheckCircleFill)
                ->description(__('Availability for operations.'))
                ->schema([
                    Toggle::make('is_active')
                        ->default(true),
                ])
                ->columnSpanFull(),

            AuditSection::make(),
        ];
    }

    /**
     * Generate a visibleJs expression that checks if the roles multi-select
     * includes the given role code(s). Accepts string or array. Resolves role ID(s) from DB by code(s).
     * Results are memoized for the duration of the request to avoid redundant cache lookups.
     */
    private static function roleVisibleJs(string|array $roleCodes): string
    {
        static $cache = [];

        $cacheKey = is_array($roleCodes) ? implode(',', $roleCodes) : $roleCodes;
        $cacheKey = (int) $cacheKey;

        return $cache[$cacheKey] ??= self::buildRoleVisibleJs($roleCodes);
    }

    private static function buildRoleVisibleJs(string|array $roleCodes): string
    {
        $roleCodesArr = is_array($roleCodes) ? $roleCodes : [$roleCodes];
        $roleIds = app(PartnerRoleLookup::class)->idsFor($roleCodesArr);

        $roleIdsJs = $roleIds->map(fn ($id) => is_numeric($id) ? $id : "'{$id}'")->implode(', ');

        return sprintf(
            <<<'JS'
                (() => {
                    const roles = $get('roles') ?? [];
                    const ids = [%s];
                    return ids.some(id => roles.includes(id) || roles.includes(String(id)));
                })()
                JS,
            $roleIdsJs,
        );
    }
}
