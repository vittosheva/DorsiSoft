<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Schemas;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TableSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\EmptyState;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Modules\Core\Support\Actions\ClearAction;
use Modules\Core\Support\Forms\DatePickers\IssueDatePicker;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Inventory\Enums\ProductTypeEnum;
use Modules\Inventory\Models\Product;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\CarrierVehicle;
use Modules\People\Support\Forms\Selects\CustomerBusinessPartnerSelect;
use Modules\Sales\Enums\DeliveryGuideCarrierTypeEnum;
use Modules\Sales\Enums\DeliveryGuideTransferReasonEnum;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Tables\CarriersTable;
use Modules\Sales\Filament\CoreApp\Resources\DeliveryGuides\Tables\DeliveryGuideProductPickerTable;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\Tables\AuthorizedInvoicesTable;
use Modules\Sales\Models\Invoice;
use Modules\Sales\Support\Forms\Components\ElectronicDocumentStatusBadges;
use Modules\Sales\Support\Forms\Sections\AdditionalInfoRepeaterSection;
use Modules\Sri\Enums\SriDocumentTypeEnum;
use Modules\Sri\Support\Components\FusedGroups\SequenceEmissionFusedGroup;
use Modules\Sri\Support\Forms\Concerns\HasSriEstablishmentFields;

final class DeliveryGuideForm
{
    use HasSriEstablishmentFields;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        self::documentDataSection()
                            ->columnSpan(6),
                        self::carrierSection()
                            ->columnSpan(6),
                    ]),

                Grid::make(12)
                    ->schema([
                        Group::make([
                            self::transportDataSection(),
                            AdditionalInfoRepeaterSection::make(),
                        ])
                            ->columns(1)
                            ->columnSpan(4),
                        self::recipientsSection()
                            ->columnSpan(8),
                    ]),
            ])
            ->columns(1);
    }

    private static function documentDataSection(): Section
    {
        return Section::make(__('Emission Data'))
            ->icon(Heroicon::DocumentText)
            ->afterHeader(ElectronicDocumentStatusBadges::make())
            ->schema([
                CodeTextInput::make('code')
                    ->autoGenerateFromModel(
                        scope: fn () => [
                            'company_id' => Filament::getTenant()?->getKey(),
                        ],
                    )
                    ->columnSpan(4),

                IssueDatePicker::make('issue_date')
                    ->columnSpan(3),

                SequenceEmissionFusedGroup::makeForDocumentType(SriDocumentTypeEnum::DeliveryGuide),
            ])
            ->columns(12);
    }

    private static function carrierSection(): Section
    {
        return Section::make(__('Carrier'))
            ->icon(Heroicon::OutlinedTruck)
            ->afterHeader(
                Radio::make('carrier_type')
                    ->hiddenLabel()
                    ->options(DeliveryGuideCarrierTypeEnum::class)
                    ->default(DeliveryGuideCarrierTypeEnum::Own)
                    ->inline()
                    ->live(),
            )
            ->schema([
                // Own transport — select from system carriers via modal
                Group::make([
                    EmptyState::make(__('Carrier'))
                        ->description(__('Search and select a carrier from the system'))
                        ->icon(Heroicon::OutlinedTruck)
                        ->footer([
                            self::carrierModalSelect(__('Select Carrier')),
                        ])
                        ->visible(fn (Get $get): bool => blank($get('carrier_id')))
                        ->visibleJs(<<<'JS'
                                ! $get('carrier_id')
                            JS)
                        ->compact()
                        ->columnSpanFull(),

                    // Card wrapping carrier header + vehicle selector
                    Group::make([
                        TextEntry::make('carrier_selected_display')
                            ->hiddenLabel()
                            ->state(fn (Get $get): HtmlString => self::buildCarrierCard($get('carrier_id')))
                            ->html(),

                        Select::make('carrier_vehicle_id')
                            ->label(__('License Plate'))
                            ->options(function (Get $get): array {
                                $carrierId = $get('carrier_id');
                                if (blank($carrierId)) {
                                    return [];
                                }

                                return CarrierVehicle::query()
                                    ->select(['id', 'vehicle_plate', 'driver_name'])
                                    ->where('business_partner_id', $carrierId)
                                    ->where('is_active', true)
                                    ->orderBy('vehicle_plate')
                                    ->get()
                                    ->mapWithKeys(fn (CarrierVehicle $v): array => [
                                        $v->id => $v->vehicle_plate.($v->driver_name ? ' — '.$v->driver_name : ''),
                                    ])
                                    ->toArray();
                            })
                            ->live()
                            ->afterStateHydrated(function (Select $component): void {
                                $record = $component->getLivewire()->getRecord();
                                if (! $record || blank($record->carrier_plate) || blank($record->carrier_id)) {
                                    return;
                                }

                                $vehicleId = CarrierVehicle::where('business_partner_id', $record->carrier_id)
                                    ->where('vehicle_plate', $record->carrier_plate)
                                    ->value('id');

                                if ($vehicleId) {
                                    $component->state($vehicleId);
                                }
                            })
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if (blank($state)) {
                                    $set('carrier_plate', null);
                                    $set('carrier_driver_name', null);

                                    return;
                                }

                                $vehicle = CarrierVehicle::find($state, ['id', 'vehicle_plate', 'driver_name']);
                                if (! $vehicle) {
                                    return;
                                }

                                $set('carrier_plate', $vehicle->vehicle_plate);
                                $set('carrier_driver_name', $vehicle->driver_name);
                            })
                            ->required()
                            ->dehydrated(false),
                    ])
                        ->columns(2)
                        ->extraAttributes(['class' => 'rounded-xl border border-gray-300 bg-gray-100 px-4 py-3 dark:border-primary-700 dark:bg-primary-900/20'])
                        ->visible(fn (Get $get): bool => filled($get('carrier_id')))
                        ->visibleJs(<<<'JS'
                            !! $get('carrier_id')
                        JS)
                        ->columnSpanFull(),

                    Group::make([
                        self::carrierModalSelect(__('Change Carrier'), asLink: true),

                        ClearAction::make('clear')
                            ->action(function (Set $set): void {
                                $set('carrier_id', null);
                                $set('carrier_name', null);
                                $set('carrier_identification', null);
                                $set('carrier_plate', null);
                                $set('carrier_driver_name', null);
                                $set('carrier_vehicle_id', null);
                            }),
                    ])
                        ->columns(4)
                        ->visible(fn (Get $get, $operation): bool => $operation === Operation::Edit->value && filled($get('carrier_id')))
                        ->visibleJs(<<<'JS'
                            !! $get('carrier_id')
                        JS)
                        ->columnSpanFull(),

                    Hidden::make('carrier_id')->dehydrated(),
                    Hidden::make('carrier_name')->dehydrated(),
                    Hidden::make('carrier_identification')->dehydrated(),
                    Hidden::make('carrier_plate')->dehydrated(),
                    Hidden::make('carrier_driver_name')->dehydrated(),
                ])
                    ->visible(fn (Get $get): bool => $get('carrier_type') === DeliveryGuideCarrierTypeEnum::Own)
                    ->columns(1)
                    ->columnSpanFull(),

                // Third-party transport — manual text entry
                Fieldset::make(__('Carrier Data'))
                    ->schema([
                        TextInput::make('carrier_identification')
                            ->label(__('RUC / ID'))
                            ->required()
                            ->maxLength(30)
                            ->columnSpan(3),

                        TextInput::make('carrier_name')
                            ->label(__('Legal Name'))
                            ->required()
                            ->maxLength(200)
                            ->columnSpan(9),

                        TextInput::make('carrier_driver_name')
                            ->label('Driver')
                            ->maxLength(200)
                            ->columnSpan(6),

                        TextInput::make('carrier_plate')
                            ->label(__('License Plate'))
                            ->required()
                            ->maxLength(20)
                            ->columnSpan(3),
                    ])
                    ->columns(12)
                    ->visible(fn (Get $get): bool => $get('carrier_type') === DeliveryGuideCarrierTypeEnum::ThirdParty),
            ])
            ->columns(1);
    }

    private static function carrierModalSelect(string $label, bool $asLink = false): ModalTableSelect
    {
        return ModalTableSelect::make('carrier_id')
            ->hiddenLabel()
            ->relationship(
                name: 'carrier',
                titleAttribute: 'legal_name',
                modifyQueryUsing: fn ($query) => $query->carriers()->select(['id', 'legal_name', 'identification_number']),
            )
            ->getOptionLabelUsing(fn () => '')
            ->tableConfiguration(CarriersTable::class)
            ->live()
            ->afterStateUpdated(function ($state, Set $set): void {
                if (blank($state)) {
                    $set('carrier_name', null);
                    $set('carrier_identification', null);
                    $set('carrier_driver_name', null);
                    $set('carrier_plate', null);
                    $set('carrier_vehicle_id', null);

                    return;
                }

                $partner = BusinessPartner::withoutGlobalScopes()
                    ->with([
                        'carrierVehicles' => fn ($query) => $query
                            ->where('is_active', true)
                            ->orderBy('vehicle_plate')
                            ->select(['id', 'business_partner_id', 'driver_name', 'vehicle_plate']),
                    ])
                    ->select(['id', 'legal_name', 'identification_number'])
                    ->find($state);

                if (! $partner) {
                    return;
                }

                $firstVehicle = $partner->carrierVehicles->first();

                $set('carrier_name', $partner->legal_name);
                $set('carrier_identification', $partner->identification_number);
                $set('carrier_vehicle_id', $firstVehicle?->id);
                $set('carrier_driver_name', $firstVehicle?->driver_name);
                $set('carrier_plate', $firstVehicle?->vehicle_plate);
            })
            ->selectAction(function (Action $action) use ($label, $asLink): Action {
                $action = $action
                    ->label($label)
                    ->modalHeading(__('Search carriers'))
                    ->modalSubmitActionLabel(__('Select'))
                    ->size(Size::Small);

                if ($asLink) {
                    return $action
                        ->link()
                        ->color('gray');
                }

                return $action->button();
            });
    }

    private static function buildCarrierCard(string|int|null $carrierId): HtmlString
    {
        if (! $carrierId) {
            return new HtmlString('');
        }

        $partner = BusinessPartner::withoutGlobalScopes()
            ->select(['id', 'legal_name', 'identification_number'])
            ->find($carrierId);

        if (! $partner) {
            return new HtmlString('');
        }

        $name = e($partner->legal_name);
        $ruc = e($partner->identification_number ?? '—');

        return new HtmlString(<<<HTML
            <p class="text-sm font-semibold text-primary-700 dark:text-primary-400">{$name}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">{$ruc}</p>
        HTML);
    }

    private static function transportDataSection(): Section
    {
        return Section::make(__('Transport Data'))
            ->icon(Heroicon::OutlinedMapPin)
            ->schema([
                Textarea::make('origin_address')
                    ->required()
                    ->maxLength(300)
                    ->default(fn () => Filament::getTenant()?->tax_address)
                    ->columnSpanFull(),

                DatePicker::make('transport_start_date')
                    ->required()
                    ->default(now()->toDateString()),

                DatePicker::make('transport_end_date')
                    ->required()
                    ->default(now()->toDateString()),
            ])
            ->columns(2);
    }

    private static function recipientsSection(): Section
    {
        return Section::make(__('Recipients'))
            ->icon(Heroicon::OutlinedCube)
            ->schema([
                Repeater::make('recipients')
                    ->relationship('recipients')
                    ->hiddenLabel()
                    ->addActionLabel(__('Add Recipient'))
                    ->orderColumn('sort_order')
                    ->schema([
                        CustomerBusinessPartnerSelect::make('business_partner_id')
                            ->afterSelection(function ($state, Set $set): void {
                                if (! $state) {
                                    $set('recipient_name', null);
                                    $set('recipient_identification_type', null);
                                    $set('recipient_identification', null);

                                    return;
                                }

                                $partner = BusinessPartner::withoutGlobalScopes()
                                    ->select([
                                        'id',
                                        'legal_name',
                                        'identification_type',
                                        'identification_number',
                                        'tax_address',
                                    ])
                                    ->find($state);

                                if (! $partner) {
                                    return;
                                }

                                $set('recipient_name', $partner->legal_name);
                                $set('recipient_identification_type', $partner->identification_type?->value ?? $partner->identification_type);
                                $set('recipient_identification', $partner->identification_number);
                                $set('destination_address', $partner->tax_address);
                            })
                            ->showFinalConsumer(false)
                            ->columnSpan(8),

                        Select::make('transfer_reason')
                            ->options(DeliveryGuideTransferReasonEnum::class)
                            ->required()
                            ->columnSpan(4),

                        Textarea::make('destination_address')
                            ->required()
                            ->maxLength(300)
                            ->columnSpan(4),

                        Textarea::make('route')
                            ->maxLength(300)
                            ->placeholder(__('Optional'))
                            ->columnSpan(4),

                        TextInput::make('destination_establishment_code')
                            ->maxLength(3)
                            ->placeholder(__('e.g.: 001'))
                            ->columnSpan(4),

                        Group::make([
                            EmptyState::make(__('Linked Invoice'))
                                ->description(__('Optionally link an authorized invoice to this recipient'))
                                ->icon(InvoiceResource::getNavigationIcon())
                                ->footer([
                                    self::invoiceModalSelect(__('Link Invoice')),
                                ])
                                ->visible(fn (Get $get): bool => blank($get('invoice_id')))
                                ->compact()
                                ->columnSpanFull(),

                            TextEntry::make('invoice_selected_display')
                                ->hiddenLabel()
                                ->state(fn (Get $get): HtmlString => self::buildInvoiceCard($get('invoice_id')))
                                ->html()
                                ->visible(fn (Get $get): bool => filled($get('invoice_id')))
                                ->columnSpanFull(),

                            Group::make([
                                self::invoiceModalSelect(__('Change Invoice'), asLink: true),

                                ClearAction::make('clear_invoice')
                                    ->action(function (Set $set): void {
                                        $set('invoice_id', null);
                                    }),
                            ])
                                ->columns(4)
                                ->visible(fn (Get $get, $operation): bool => $operation === Operation::Edit->value && filled($get('invoice_id')))
                                ->columnSpanFull(),

                            Hidden::make('invoice_id')->dehydrated(),
                        ])
                            ->visible(fn (Get $get): bool => filled($get('business_partner_id')))
                            ->columns(1)
                            ->columnSpan(8),

                        TextInput::make('customs_doc')
                            ->maxLength(100)
                            ->placeholder(__('Optional'))
                            ->columnSpan(4),

                        self::itemsRepeater()
                            ->columnSpanFull(),

                        // Hidden snapshot fields populated via afterStateUpdated
                        Hidden::make('recipient_name')->dehydrated(),
                        Hidden::make('recipient_identification_type')->dehydrated(),
                        Hidden::make('recipient_identification')->dehydrated(),
                    ])
                    ->minItems(1)
                    ->defaultItems(1)
                    ->columns(12),
            ]);
    }

    private static function invoiceModalSelect(string $label, bool $asLink = false): ModalTableSelect
    {
        return ModalTableSelect::make('invoice_id')
            ->hiddenLabel()
            ->relationship(
                name: 'invoice',
                titleAttribute: 'code',
                modifyQueryUsing: fn ($query) => $query
                    ->where('company_id', Filament::getTenant()?->getKey())
                    ->select(['id', 'code']),
            )
            ->tableConfiguration(AuthorizedInvoicesTable::class)
            ->tableArguments(fn (Get $get) => [
                'business_partner_id' => $get('business_partner_id'),
                'only_with_product_items' => true,
            ])
            ->getOptionLabelUsing(fn () => '')
            ->live()
            ->selectAction(function (Action $action) use ($label, $asLink): Action {
                $action = $action
                    ->label($label)
                    ->modalHeading(__('Search invoices'))
                    ->modalSubmitActionLabel(__('Select'));

                if ($asLink) {
                    return $action->link()->color('gray');
                }

                return $action->button();
            });
    }

    private static function buildInvoiceCard(string|int|null $invoiceId): HtmlString
    {
        if (! $invoiceId) {
            return new HtmlString('');
        }

        $invoice = Invoice::withoutGlobalScopes()
            ->with('businessPartner:id,legal_name,identification_number')
            ->select(['id', 'code', 'issue_date', 'total', 'business_partner_id'])
            ->find($invoiceId);

        if (! $invoice) {
            return new HtmlString('');
        }

        $code = e($invoice->code);
        $partnerName = e($invoice->businessPartner?->legal_name ?? '—');
        $partnerRuc = e($invoice->businessPartner?->identification_number ?? '—');
        $rawDate = $invoice->getAttribute('issue_date');
        $date = $rawDate ? Carbon::parse($rawDate)->format('d/m/Y') : '—';
        $total = number_format((float) $invoice->total, 2);

        return new HtmlString(<<<HTML
            <div class="rounded-xl border border-gray-300 bg-gray-100 px-4 py-3 dark:border-warning-700 dark:bg-warning-900/20">
                <p class="text-sm font-semibold text-primary-700 dark:text-primary-400">{$code}</p>
                <p class="text-sm text-gray-700 dark:text-gray-300">{$partnerName} &nbsp;·&nbsp; {$partnerRuc}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{$date} &nbsp;·&nbsp; \${$total}</p>
            </div>
        HTML);
    }

    private static function itemsRepeater(): Repeater
    {
        return Repeater::make('items')
            ->relationship('items')
            ->addActionLabel(__('Add Item'))
            ->orderColumn('sort_order')
            ->table([
                TableColumn::make(__('Code')),
                TableColumn::make(__('Description'))->width('70%'),
                TableColumn::make(__('Quantity')),
            ])
            ->schema([
                Hidden::make('product_id'),

                TextInput::make('product_code')
                    ->maxLength(50),

                TextInput::make('product_name')
                    ->label(__('Description'))
                    ->required()
                    ->maxLength(300)
                    ->columnSpan(4)
                    ->prefixActions([
                        Action::make('select_product')
                            ->hiddenLabel()
                            ->icon(Heroicon::MagnifyingGlass)
                            ->tooltip(fn ($operation) => $operation !== 'view' ? __('Search product') : null)
                            ->fillForm(fn (Get $get): array => [
                                'invoice_id' => $get('../../invoice_id'),
                            ])
                            ->schema([
                                Hidden::make('invoice_id'),
                                TableSelect::make('selected_product')
                                    ->hiddenLabel()
                                    ->relationship('product')
                                    ->tableConfiguration(DeliveryGuideProductPickerTable::class)
                                    ->tableArguments(fn (Get $get) => [
                                        'invoice_id' => $get('invoice_id'),
                                    ]),
                            ])
                            ->action(function (array $data, Set $set): void {
                                $productId = $data['selected_product'] ?? null;

                                if (blank($productId)) {
                                    return;
                                }

                                $product = Product::query()
                                    ->select(['id', 'code', 'name'])
                                    ->whereIn('type', [ProductTypeEnum::Product, ProductTypeEnum::Kit])
                                    ->find($productId);

                                if (! $product) {
                                    return;
                                }

                                $set('product_id', $product->id);
                                $set('product_code', $product->code);
                                $set('product_name', $product->name);
                            })
                            ->modalHeading(__('Select product'))
                            ->modalSubmitActionLabel(__('Confirm selection')),
                    ]),

                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->extraInputAttributes(['class' => 'text-right'])
                    ->minValue(0.000001)
                    ->default(1),
            ])
            ->minItems(1)
            ->defaultItems(1)
            ->columns(6);
    }
}
