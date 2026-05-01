<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\CoreApp\Resources\Products\Schemas;

use BackedEnum;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use JeffersonGoncalves\Filament\QrCodeField\Forms\Components\QrCodeInput;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Services\FileStoragePathService;
use Modules\Core\Support\Components\Sections\AuditSection;
use Modules\Core\Support\Forms\TextInputs\CodeTextInput;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Core\Support\Forms\TextInputs\NameTextInput;
use Modules\Core\Support\Forms\TextInputs\QuantityTextInput;
use Modules\Finance\Enums\TaxTypeEnum;
use Modules\Finance\Models\PriceList;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Enums\AbcClassificationEnum;
use Modules\Inventory\Enums\BarcodeTypeEnum;
use Modules\Inventory\Enums\ProductTypeEnum;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\BrandResource;
use Modules\Inventory\Filament\CoreApp\Resources\Brands\Schemas\BrandForm;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\CategoryResource;
use Modules\Inventory\Filament\CoreApp\Resources\Categories\Schemas\CategoryForm;
use Modules\Inventory\Filament\CoreApp\Resources\Units\Schemas\UnitForm;
use Modules\Inventory\Filament\CoreApp\Resources\Units\UnitResource;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\Unit;
use Modules\System\Enums\TaxCalculationTypeEnum;

final class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        ...self::leftColumn(),
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

    private static function productTypeJs(): string
    {
        return sprintf('$get(\'type\') === \'%s\'', ProductTypeEnum::Product->value);
    }

    private static function qrTypeJs(): string
    {
        return sprintf('$get(\'barcode_type\') === \'%s\'', BarcodeTypeEnum::Qr->value);
    }

    private static function leftColumn(): array
    {
        return [
            self::basicInfoSection(),
            Tabs::make('Details')
                ->columnSpanFull()
                ->tabs([
                    Tab::make(__('General'))
                        ->schema([
                            RichEditor::make('description')
                                ->columnSpanFull(),
                        ]),

                    Tab::make(__('Costs & Prices'))
                        ->schema([
                            self::costSection(),
                            self::priceListSection(),
                        ]),

                    Tab::make(__('Inventory'))
                        ->schema([
                            self::physicalSection(),
                            self::stockSection(),
                            self::identificationSection(),
                            self::qrCodeSection(),
                        ])
                        ->visibleJs(sprintf('%s && $get(\'is_inventory\') === true', self::productTypeJs())),
                ]),
        ];
    }

    private static function rightColumn(): array
    {
        return [
            Section::make(__('Image'))
                ->icon(Heroicon::Photo)
                ->schema([
                    FileUpload::make('image_url')
                        ->hiddenLabel()
                        ->image()
                        ->disk(fn () => FileStoragePathService::getDisk(FileTypeEnum::ProductImages))
                        ->directory(fn ($record) => FileStoragePathService::getPath(FileTypeEnum::ProductImages, $record))
                        ->visibility(fn () => FileStoragePathService::getVisibility(FileTypeEnum::ProductImages))
                        ->acceptedFileTypes(fn () => FileStoragePathService::getAcceptedTypes(FileTypeEnum::ProductImages))
                        ->maxSize(fn () => FileStoragePathService::getMaxSizeKb(FileTypeEnum::ProductImages)),
                ])
                ->columnSpanFull(),

            Section::make(__('Classification'))
                ->icon(Heroicon::Folder)
                ->schema([
                    Select::make('category_id')
                        ->relationship(
                            'category',
                            'name',
                            fn (Builder $query) => $query
                                ->select(['id', 'name'])
                                ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                        )
                        ->createOptionForm(fn (Schema $schema) => $schema->components(CategoryForm::configure($schema)->getComponents()))
                        ->preload()
                        ->required()
                        ->searchable()
                        ->prefixIcon(CategoryResource::getNavigationIcon()),

                    Select::make('brand_id')
                        ->relationship(
                            'brand',
                            'name',
                            fn (Builder $query) => $query
                                ->select(['id', 'name'])
                                ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                        )
                        ->createOptionForm(fn (Schema $schema) => $schema->components(BrandForm::configure($schema)->getComponents()))
                        ->preload()
                        ->searchable()
                        ->prefixIcon(BrandResource::getNavigationIcon()),

                    Select::make('unit_id')
                        ->relationship(
                            'unit',
                            'name',
                            fn (Builder $query) => $query
                                ->select(['id', 'name', 'symbol'])
                                ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                        )
                        ->getOptionLabelFromRecordUsing(fn (Unit $record) => "{$record->name}".($record->symbol ? " ({$record->symbol})" : ''))
                        ->preload()
                        ->required()
                        ->searchable()
                        ->createOptionForm(fn (Schema $schema) => $schema->components(UnitForm::configure($schema)->getComponents()))
                        ->prefixIcon(UnitResource::getNavigationIcon()),
                ])
                ->columns(1)
                ->columnSpanFull(),

            Section::make(__('Options'))
                ->icon(Heroicon::Flag)
                ->schema([
                    Toggle::make('is_inventory')
                        ->default(false)
                        ->visibleJs(self::productTypeJs()),

                    Toggle::make('is_for_sale')
                        ->default(false),

                    Toggle::make('is_for_purchase')
                        ->default(false),

                    /* Toggle::make('is_active')
                    ->visibleOn([Operation::Edit, Operation::View])
                    ->default(true), */
                ])
                ->columnSpanFull(),
            AuditSection::make(),
        ];
    }

    private static function basicInfoSection(): Section
    {
        return Section::make(__('Basic Information'))
            ->icon(Heroicon::DocumentText)
            ->afterHeader([
                Toggle::make('is_active')
                    ->visibleOn([Operation::Edit, Operation::View])
                    ->default(true),
            ])
            ->schema([
                CodeTextInput::make()
                    ->autoGenerateFromModel(
                        modelClass: Product::class,
                        prefix: Product::getCodePrefix(),
                        scope: fn () => ['company_id' => Filament::getTenant()?->getKey()],
                    )
                    ->columnSpan(2),

                NameTextInput::make()
                    ->autofocus()
                    ->columnSpan(6),

                Radio::make('type')
                    ->options(ProductTypeEnum::options())
                    ->default(ProductTypeEnum::default())
                    ->required()
                    ->inline()
                    ->columnSpan(4),
            ])
            ->columns(12)
            ->columnSpanFull();
    }

    private static function priceListSection(): Section
    {
        return Section::make(__('Prices'))
            ->icon(Heroicon::Tag)
            ->description(__('Assign this product to price lists with specific prices'))
            ->schema([
                Repeater::make('priceListItems')
                    ->hiddenLabel()
                    ->relationship('priceListItems')
                    ->table([
                        TableColumn::make(__('Price Lists'))->width('50%'),
                        TableColumn::make(__('Price'))->width('auto'),
                        TableColumn::make(__('Min quantity'))->width('auto'),
                    ])
                    ->schema([
                        Select::make('price_list_id')
                            ->options(fn () => PriceList::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rules([
                                fn (callable $get) => function (...$params) use ($get): void {
                                    [, $value, $fail] = $params;

                                    if (! $value) {
                                        return;
                                    }

                                    $allItems = $get('../../priceListItems') ?? [];
                                    $duplicates = collect($allItems)
                                        ->pluck('price_list_id')
                                        ->filter(fn ($id) => $id === $value)
                                        ->count();

                                    if ($duplicates > 1) {
                                        $fail(__('This price list is already selected in another row.'));
                                    }
                                },
                            ])
                            ->columnSpan(6),

                        MoneyTextInput::make('price')
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('min_quantity')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->dehydrateStateUsing(fn ($state) => $state ?? 1)
                            ->extraInputAttributes(['class' => 'text-right'])
                            ->required()
                            ->columnSpan(3),
                    ])
                    ->reorderable(false)
                    ->addActionLabel(__('Add Price List'))
                    ->columns(12)
                    ->columnSpanFull()
                    ->defaultItems(0),
            ]);
    }

    private static function costSection(): Section
    {
        return Section::make(__('Costs & Tax'))
            ->icon(Heroicon::CurrencyDollar)
            ->schema([
                MoneyTextInput::make('sale_price')
                    ->currencyCode(fn (): string => Filament::getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->columnSpan(3),

                MoneyTextInput::make('standard_cost')
                    ->currencyCode(fn (): string => Filament::getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->minValue(0)
                    ->default(0)
                    ->columnSpan(3),

                MoneyTextInput::make('current_unit_cost')
                    ->currencyCode(fn (): string => Filament::getTenant()?->defaultCurrency?->code ?? 'USD')
                    ->minValue(0)
                    ->default(0)
                    ->columnSpan(3),

                Select::make('taxes')
                    ->label(__('Product Taxes'))
                    ->relationship(
                        'taxes',
                        'name',
                        fn (Builder $query) => $query
                            ->select(['fin_taxes.id', 'fin_taxes.name', 'fin_taxes.type', 'fin_taxes.rate', 'fin_taxes.calculation_type'])
                            ->active()
                            ->whereIn('fin_taxes.type', self::allowedProductTaxTypes())
                            ->orderBy('type')
                            ->orderBy('rate')
                            ->orderBy('name')
                            ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                    )
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->helperText(__('Assign at most one IVA and one ICE tax to the product.'))
                    ->getOptionLabelFromRecordUsing(function (Tax $record): string {
                        $calculationType = $record->calculation_type instanceof TaxCalculationTypeEnum
                            ? $record->calculation_type
                            : TaxCalculationTypeEnum::tryFrom((string) $record->calculation_type);

                        $formattedRate = $calculationType === TaxCalculationTypeEnum::Fixed
                            ? '$'.number_format((float) $record->rate, 2)
                            : number_format((float) $record->rate, 2).' %';

                        $type = $record->type instanceof BackedEnum ? $record->type->value : (string) $record->type;

                        return "{$record->name} [{$type} | {$formattedRate}]";
                    })
                    ->rules([
                        fn (): Closure => self::singleTaxPerTypeRule(),
                    ])
                    ->saveRelationshipsUsing(function (Select $component, Model $record, mixed $state): void {
                        $selectedIds = collect($state)
                            ->filter()
                            ->map(fn (mixed $id): int => (int) $id)
                            ->unique()
                            ->values();

                        /** @var Product $record */
                        if ($selectedIds->isEmpty()) {
                            $record->taxes()->sync([]);

                            return;
                        }

                        $syncData = Tax::query()
                            ->select(['id', 'type'])
                            ->whereKey($selectedIds)
                            ->whereIn('type', self::allowedProductTaxTypes())
                            ->get()
                            ->mapWithKeys(fn (Tax $tax): array => [
                                $tax->getKey() => [
                                    'tax_type' => $tax->type instanceof BackedEnum ? $tax->type->value : (string) $tax->type,
                                ],
                            ])
                            ->all();

                        $record->taxes()->sync($syncData);
                    })
                    ->columnSpan(3),
            ])
            ->columns(12)
            ->columnSpanFull();
    }

    /**
     * @return list<string>
     */
    private static function allowedProductTaxTypes(): array
    {
        return [
            TaxTypeEnum::Iva->value,
            TaxTypeEnum::Ice->value,
        ];
    }

    private static function singleTaxPerTypeRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $selectedIds = collect($value)
                ->filter()
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values();

            if ($selectedIds->isEmpty()) {
                return;
            }

            $selectedTaxes = Tax::query()
                ->select(['type'])
                ->whereKey($selectedIds)
                ->get();

            $unsupportedTypes = $selectedTaxes
                ->map(fn (Tax $tax): string => $tax->type instanceof BackedEnum ? $tax->type->value : (string) $tax->type)
                ->reject(fn (string $type): bool => in_array($type, self::allowedProductTaxTypes(), true))
                ->unique()
                ->values();

            if ($unsupportedTypes->isNotEmpty()) {
                $fail(__('Only IVA and ICE taxes can be assigned to the product.'));

                return;
            }

            $duplicateTypes = $selectedTaxes
                ->map(fn (Tax $tax): string => $tax->type instanceof BackedEnum ? $tax->type->value : (string) $tax->type)
                ->duplicates()
                ->unique()
                ->values();

            if ($duplicateTypes->isEmpty()) {
                return;
            }

            $fail(__('Only one tax per type is allowed. Repeated types: :types.', ['types' => $duplicateTypes->implode(', ')]));
        };
    }

    private static function stockSection(): Section
    {
        return Section::make(__('Stock Parameters'))
            ->icon(Heroicon::CubeTransparent)
            ->schema([
                QuantityTextInput::make('min_stock')
                    ->columnSpan(3),

                QuantityTextInput::make('max_stock')
                    ->columnSpan(3),

                QuantityTextInput::make('reorder_point')
                    ->columnSpan(3),

                Select::make('abc_classification')
                    ->options(AbcClassificationEnum::options())
                    ->nullable()
                    ->columnSpan(3),

                Toggle::make('tracks_lots')
                    ->default(false)
                    ->columnSpan(3),

                Toggle::make('tracks_serials')
                    ->default(false)
                    ->columnSpan(3),
            ])
            ->visibleJs(sprintf('$get(\'is_inventory\') === true && %s', self::productTypeJs()))
            ->columns(12)
            ->columnSpanFull();
    }

    private static function identificationSection(): Section
    {
        return Section::make(__('Codes'))
            ->icon(Heroicon::Identification)
            ->schema([
                TextInput::make('sku')
                    ->maxLength(50)
                    ->nullable()
                    ->columnSpan(3),

                Select::make('barcode_type')
                    ->options(BarcodeTypeEnum::options())
                    ->nullable()
                    ->columnSpan(3),

                QrCodeInput::make('barcode')
                    ->icon(str()->of(Heroicon::OutlinedQrCode->value)->prepend('heroicon-')->toString())
                    ->maxLength(50)
                    ->requiredIf('barcode_type', BarcodeTypeEnum::Qr->value)
                    ->nullable()
                    ->visibleJs(<<<'JS'
                        $get('barcode_type') && $get('barcode_type') !== 'QR'
                    JS)
                    ->columnSpan(6),
            ])
            ->visibleJs(self::productTypeJs())
            ->columns(12)
            ->columnSpanFull();
    }

    private static function qrCodeSection(): Section
    {
        return Section::make(__('QR Code'))
            ->icon(Heroicon::QrCode)
            ->schema([
                TextEntry::make('qr_code_preview')
                    ->hiddenLabel()
                    ->state(fn (?Product $record) => self::renderQrCodePreview($record)),
            ])
            ->visibleJs(sprintf('%s && %s', self::productTypeJs(), self::qrTypeJs()))
            ->columnSpanFull();
    }

    private static function physicalSection(): Section
    {
        return Section::make(__('Physical Characteristics'))
            ->icon(Heroicon::CubeTransparent)
            ->schema([
                TextInput::make('weight')
                    ->numeric()
                    ->minValue(0)
                    ->nullable()
                    ->suffix('kg')
                    ->columnSpan(3),

                TextInput::make('volume')
                    ->numeric()
                    ->minValue(0)
                    ->nullable()
                    ->suffix('m³')
                    ->columnSpan(3),
            ])
            ->visibleJs(self::productTypeJs())
            ->columns(12)
            ->columnSpanFull();
    }

    private static function renderQrCodePreview(?Product $record): HtmlString|string
    {
        if ($record === null || blank($record->qr_code_path)) {
            return __('QR code will be available after saving.');
        }

        $disk = Storage::disk((string) config('filament.default_filesystem_disk', config('filesystems.default')));

        if (! $disk->exists($record->qr_code_path)) {
            return __('QR code file is not available.');
        }

        $url = Storage::url($record->qr_code_path);

        return new HtmlString(sprintf(
            '<div class="space-y-2"><img src="%s" alt="%s" class="mx-auto h-40 w-40 rounded border border-gray-200 p-2" /><p class="text-center text-xs text-gray-500 break-all">%s</p></div>',
            e($url),
            e(__('Product QR code')),
            e($record->barcode ?? ''),
        ));
    }
}
