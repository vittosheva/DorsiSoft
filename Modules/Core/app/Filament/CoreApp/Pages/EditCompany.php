<?php

declare(strict_types=1);

namespace Modules\Core\Filament\CoreApp\Pages;

use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire as LivewireSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Enums\FileTypeEnum;
use Modules\Core\Enums\SubscriptionPlanEnum;
use Modules\Core\Livewire\CompanyEstablishmentsManager;
use Modules\Core\Models\Company;
use Modules\Core\Services\FileStoragePathService;
use Modules\Core\Services\SriPayloadHydrator;
use Modules\Core\Services\SriValidationNotifier;
use Modules\Core\Support\Forms\Selects\CurrencySelect;
use Modules\Core\Support\Forms\TextInputs\MoneyTextInput;
use Modules\Core\Support\Forms\TextInputs\RucTextInput;
use Modules\Core\Support\Sri\SriPayloadMapper;
use Modules\Core\Traits\LocationSelects;
use Modules\Finance\Enums\TaxTypeEnum;
use Modules\Finance\Support\Forms\Selects\TaxSelect;
use Modules\Sri\Enums\SriEnvironmentEnum;
use Modules\Sri\Enums\SriRegimeTypeEnum;
use Modules\Sri\Exceptions\XmlSigningException;
use Modules\Sri\Services\CertificateService;
use Modules\Sri\Services\Sri\Contracts\SriServiceInterface;
use Modules\Workflow\Approval\ApprovalRegistry;
use Spatie\Permission\Models\Role;
use Tapp\FilamentTimezoneField\Forms\Components\TimezoneSelect;
use Throwable;

final class EditCompany extends EditTenantProfile
{
    use LocationSelects;

    public static function getLabel(): string
    {
        return __('Company profile');
    }

    public function form(Schema $schema): Schema
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

    protected function afterSave(): void
    {
        $this->tenant->refresh();
        $this->fillForm();
    }

    public function saveFormComponentOnly(Component $component): void
    {
        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $oldContainer = $component->getContainer();

            $data = Schema::make($component->getLivewire())
                ->components([$component])
                ->model($this->tenant)
                ->operation($oldContainer->getOperation())
                ->statePath('data')
                ->getState();

            $component->container($oldContainer);

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeSave($data);

            $this->callHook('beforeSave');

            $this->handleRecordUpdate($this->tenant, $data);

            $this->callHook('afterSave');
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        //$this->redirect($this->getUrl('edit', ['tenant' => $this->tenant->getKey()]));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $company = $this->tenant;

        if ($company instanceof Company) {
            $this->syncCompanySubscriptionPlan($company, $data['subscription_plan'] ?? null);
        }

        unset($data['subscription_plan']);

        $certificatePath = $data['certificate_path'] ?? $this->tenant->certificate_path;

        if (blank($certificatePath)) {
            $data['certificate_valid_from'] = null;
            $data['certificate_expiration_date'] = null;
            $data['certificate_password_encrypted'] = null;
        } else {
            $rawPassword = filled($data['certificate_password_raw'] ?? null)
                ? mb_trim($data['certificate_password_raw'])
                : null;

            if ($rawPassword !== null) {
                $certService = app(CertificateService::class);

                try {
                    $certData = $certService->loadCertificate((new Company(['certificate_path' => $certificatePath, 'certificate_password_encrypted' => $certService->encryptPassword($rawPassword)])));

                    $certParsed = openssl_x509_parse($certData['certificate']);

                    $certSerial = $certParsed['subject']['serialNumber'] ?? null;
                    $modelRuc = preg_replace('/\D/', '', $company->ruc ?? ($data['ruc'] ?? ''));

                    $certRuc = null;
                    if (is_string($certSerial) && preg_match('/(\d{10})/', $certSerial, $matches)) {
                        $certRuc = $matches[1];
                    }

                    if ($certRuc === null || ! str($modelRuc)->startsWith($certRuc)) {
                        Notification::make()
                            ->title(__("The certificate's RUC does not match the company RUC."))
                            ->danger()
                            ->send();
                        $this->halt();
                    }

                    $data['certificate_password_encrypted'] = $certService->encryptPassword($rawPassword);
                    $data['certificate_valid_from'] = $this->parseCertDate($certParsed['validFrom'])?->format('Y-m-d') ?? null;
                    $data['certificate_expiration_date'] = $this->parseCertDate($certParsed['validTo'])?->format('Y-m-d') ?? null;
                } catch (XmlSigningException $e) {
                    Notification::make()
                        ->title(__('Invalid certificate'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();

                    $this->halt();
                }
            }
        }

        unset($data['certificate_password_raw']);

        return $data;
    }

    /**
     * @return array<Component>
     */
    private static function leftColumn(): array
    {
        return [
            Tabs::make(__('Company profile'))
                ->tabs([
                    self::generalTab(),
                    self::sriDataTab(),
                    // self::establishmentsTab(),
                    self::approvalsTab(),
                    self::subscriptionsTab(),
                    self::configurationTab(),
                ])
                ->persistTab()
                ->id('company-tabs')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<Component>
     */
    private static function rightColumn(): array
    {
        return [
            self::brandingSection(),
            //self::statusSection(),
        ];
    }

    private static function generalTab(): Tab
    {
        return Tab::make(__('General'))
            ->schema([
                self::companyInfoSection(),
                self::contactSection(),
                self::addressSection(),
            ]);
    }

    private static function companyInfoSection(): Section
    {
        return Section::make(__('Company information'))
            ->schema([
                RucTextInput::make('ruc')
                    ->sriAllowedFields([
                        'legal_name',
                        'trade_name',
                        'business_activity',
                    ])
                    ->uniqueCompanyRuc(fn(?Company $record): ?Company => $record)
                    ->readOnly(fn(?Company $record) => $record !== null)
                    ->columnSpan(3),
                TextInput::make('legal_name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(5),
                TextInput::make('trade_name')
                    ->maxLength(255)
                    ->columnSpan(4),
                Textarea::make('business_activity')
                    ->rows(5)
                    ->columnSpan(6),
                Textarea::make('tax_address')
                    ->maxLength(255)
                    ->rows(3)
                    ->columnSpan(6),
            ])
            ->columns(12);
    }

    private static function contactSection(): Section
    {
        return Section::make(__('Contact'))
            ->schema([
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('website')
                    ->url()
                    ->maxLength(255),
            ])
            ->columns(3);
    }

    private static function addressSection(): Section
    {
        return Section::make(__('Address'))
            ->schema([
                Select::make('country_id')
                    ->relationship(
                        name: 'country',
                        titleAttribute: 'name',
                        modifyQueryUsing: static fn(Builder $query) => self::configureLocationQuery($query)->select(['id', 'name'])
                    )
                    ->preload(true)
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        $set('state_id', null);
                        $set('city_id', null);
                    })
                    ->partiallyRenderComponentsAfterStateUpdated(['state_id', 'city_id']),
                Select::make('state_id')
                    ->relationship(
                        name: 'state',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query, Get $get) => self::configureDependentLocationQuery($query, $get, 'country_id', 'country_id')->select(['id', 'name'])
                    )
                    ->searchable()
                    ->preload(fn(Get $get) => filled($get('country_id')))
                    ->disabled(fn(Get $get) => blank($get('country_id')))
                    ->live()
                    ->afterStateUpdated(fn($set) => $set('city_id', null))
                    ->partiallyRenderComponentsAfterStateUpdated(['city_id'])
                    ->native(false),
                Select::make('city_id')
                    ->relationship(
                        name: 'city',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query, Get $get) => self::configureDependentLocationQuery($query, $get, 'state_id', 'state_id')->select(['id', 'name'])
                    )
                    ->preload(fn(Get $get) => filled($get('state_id')))
                    ->disabled(fn(Get $get) => blank($get('state_id')))
                    ->searchable(fn(Get $get) => $get('state_id') !== null),
                TextInput::make('parish')
                    ->hidden(),
            ])
            ->columns(4);
    }

    private static function establishmentsTab(): Tab
    {
        return Tab::make(__('Establishments'))
            ->schema([
                LivewireSchema::make(
                    CompanyEstablishmentsManager::class,
                    fn($livewire): ?array => ($livewire->tenant && $livewire->tenant instanceof Company)
                        ? ['companyId' => (int) $livewire->tenant->getKey()]
                        : null,
                )
                    ->key('company-establishments')
                    ->columnSpanFull(),
            ]);
    }

    private static function sriDataTab(): Tab
    {
        return Tab::make(__('SRI data'))
            ->schema([
                self::taxSettingsSection(),
                self::taxpayerDataSection(),
            ]);
    }

    private static function taxSettingsSection(): Section
    {
        return Section::make(__('Tax settings'))
            ->headerActions([
                self::taxSettingsValidationAction(),
            ])
            ->schema([
                Select::make('tax_regime')
                    ->required()
                    ->options(SriRegimeTypeEnum::class)
                    ->enum(SriRegimeTypeEnum::class)
                    ->default(SriRegimeTypeEnum::DEFAULT),
                DatePicker::make('rimpe_expires_at')
                    ->visibleJs(<<<'JS'
                        $get('tax_regime') === 'RIMPE_NEGOCIO_POPULAR' || $get('tax_regime') === 'RIMPE_EMPRENDEDOR'
                    JS),
                Toggle::make('is_accounting_required')
                    ->inline(false)
                    ->default(false),
                Toggle::make('is_special_taxpayer')
                    ->inline(false)
                    ->default(false),
                TextInput::make('special_taxpayer_resolution')
                    ->maxLength(255)
                    ->visibleJs(<<<'JS'
                        $get('is_special_taxpayer') === true
                    JS),
            ])
            ->columns(5);
    }

    private static function taxSettingsValidationAction(): Action
    {
        return Action::make('validateSri')
            ->label(__('Validate with SRI'))
            ->icon(Heroicon::MagnifyingGlass)
            ->size(Size::ExtraSmall)
            ->color(Color::Blue)
            ->action(function (self $livewire, Set $set): void {
                $livewire->handleSriValidation($set);
            });
    }

    private static function taxpayerDataSection(): Section
    {
        return Section::make(__('Taxpayer data'))
            ->schema([
                TextInput::make('contributor_status')
                    ->maxLength(255)
                    ->readOnly()
                    ->disabled(self::shouldDisableWhenBlank('contributor_status')),
                TextInput::make('taxpayer_type')
                    ->maxLength(255)
                    ->readOnly()
                    ->disabled(self::shouldDisableWhenBlank('taxpayer_type')),
                TextInput::make('contributor_category')
                    ->maxLength(255)
                    ->readOnly()
                    ->disabled(self::shouldDisableWhenBlank('contributor_category')),
                Toggle::make('is_withholding_agent')
                    ->inline(false)
                    ->default(false)
                    ->disabled()
                    ->dehydrated(true),
                Toggle::make('is_shell_company')
                    ->inline(false)
                    ->default(false)
                    ->disabled()
                    ->dehydrated(true),
                Toggle::make('has_nonexistent_transactions')
                    ->inline(false)
                    ->default(false)
                    ->disabled()
                    ->dehydrated(true),
                Grid::make()
                    ->schema([
                        DateTimePicker::make('started_activities_at')
                            ->readOnly()
                            ->disabled(self::shouldDisableWhenBlank('started_activities_at')),
                        DateTimePicker::make('ceased_activities_at')
                            ->readOnly()
                            ->disabled(self::shouldDisableWhenBlank('ceased_activities_at')),
                        DateTimePicker::make('restarted_activities_at')
                            ->readOnly()
                            ->disabled(self::shouldDisableWhenBlank('restarted_activities_at')),
                        DateTimePicker::make('sri_updated_at')
                            ->readOnly()
                            ->disabled(self::shouldDisableWhenBlank('sri_updated_at')),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                Repeater::make('legal_representatives')
                    ->table([
                        TableColumn::make(__('Identification'))->width('40%'),
                        TableColumn::make(__('Name'))->width('60%'),
                    ])
                    ->schema([
                        TextInput::make('identification')
                            ->maxLength(255),
                        TextInput::make('name')
                            ->maxLength(255),
                    ])
                    ->compact()
                    ->reorderable()
                    ->disabled(self::shouldDisableWhenBlank('legal_representatives'))
                    ->dehydrated(true)
                    ->columns(2)
                    ->columnSpanFull(),
                Textarea::make('suspension_cancellation_reason')
                    ->rows(3)
                    ->readOnly()
                    ->disabled(self::shouldDisableWhenBlank('suspension_cancellation_reason'))
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    private static function subscriptionsTab(): Tab
    {
        return Tab::make(__('Subscriptions'))
            ->schema([
                self::subscriptionHistorySection(),
            ])
            ->lazy();
    }

    private static function subscriptionHistorySection(): Section
    {
        return Section::make(__('Subscription history'))
            ->schema([
                View::make('core::filament.company.subscription-history-table')
                    ->viewData(function (?Company $record): array {
                        if ($record === null) {
                            return ['subscriptions' => []];
                        }

                        $subscriptions = $record
                            ->subscriptions()
                            ->orderByDesc('starts_at')
                            ->get();

                        return [
                            'subscriptions' => $subscriptions,
                        ];
                    })
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    private static function configurationTab(): Tab
    {
        return Tab::make(__('Configuration'))
            ->schema([
                Section::make(__('Settings'))
                    ->schema([
                        TaxSelect::make('default_tax_id')
                            ->forType(TaxTypeEnum::Iva),
                        CurrencySelect::make('default_currency_id'),
                        TimezoneSelect::make('timezone'),
                    ])
                    ->columns(3),
                Grid::make()
                    ->schema([
                        self::electronicDocumentsSection(),
                        self::digitalCertificateSection(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function electronicDocumentsSection(): Section
    {
        return Section::make(__('Electronic Documents - SRI'))
            ->icon(Heroicon::OutlinedCpuChip)
            ->schema([
                Radio::make('sri_environment')
                    ->options(SriEnvironmentEnum::class)
                    ->enum(SriEnvironmentEnum::class)
                    ->default(SriEnvironmentEnum::DEFAULT)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsible();
    }

    private static function digitalCertificateSection(): Section
    {
        return Section::make(__('Digital Certificate'))
            ->icon(Heroicon::OutlinedKey)
            ->schema([
                FileUpload::make('certificate_path')
                    ->label(__('Digital Certificate (.p12 / .pfx)'))
                    ->helperText(function ($state, $record) {
                        if (! empty($state) || $record->certificate_path) {
                            return __('A certificate file is already uploaded. Upload a new one to replace it.');
                        }

                        return __('Upload your SRI digital signing certificate.');
                    })
                    ->acceptedFileTypes(['application/x-pkcs12', '.p12', '.pfx'])
                    ->disk('local')
                    ->directory(fn(?Company $record): string => $record?->ruc
                        ? "tenants/{$record->ruc}/certificates"
                        : 'certificates/pending')
                    ->visibility('private')
                    ->downloadable()
                    ->maxSize(1024),

                TextInput::make('certificate_password_raw')
                    ->password()
                    ->revealable()
                    ->helperText(function (?Model $record) {
                        if (filled($record->certificate_path)) {
                            return __('Enter the password to encrypt and store it securely. Leave blank to keep the existing password.');
                        }

                        return null;
                    })
                    ->required(function (Get $get) {
                        if (filled($get('certificate_path'))) {
                            return blank($get('certificate_password_encrypted')) && blank($get('certificate_password_raw'));
                        }

                        return blank($get('certificate_password_encrypted')) && blank($get('certificate_password_raw'));
                    }),

                Flex::make([
                    TextInput::make('certificate_valid_from')
                        ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('d/m/Y') : null)
                        ->dehydrated(false)
                        ->disabled(),

                    TextInput::make('certificate_expiration_date')
                        ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->format('d/m/Y') : null)
                        ->dehydrated(false)
                        ->disabled(),
                ])
                    ->visible(fn(?Company $record) => filled($record->certificate_path) && filled($record->certificate_valid_from) && filled($record->certificate_expiration_date)),

                Callout::make()
                    ->description(__('Your signature key is stored securely and in encrypted form in our servers.'))
                    ->icon(Heroicon::OutlinedLightBulb)
                    ->iconColor(Color::Yellow)
                    ->warning(),
            ]);
    }

    private static function approvalsTab(): Tab
    {
        return Tab::make(__('Approvals'))
            ->schema([
                self::approvalFlowsSection(),
                self::approvalHistorySection(),
            ])
            ->lazy();
    }

    private static function approvalFlowsSection(): Section
    {
        return Section::make(__('Approval flows'))
            ->description(__('Enable and configure approval workflows for this company. Each flow can require specific roles and a minimum document amount.'))
            ->schema([
                Repeater::make('approvalSettings')
                    ->relationship(
                        name: 'approvalSettings',
                        modifyQueryUsing: fn($query) => $query->select(['id', 'company_id', 'flow_key', 'min_amount', 'required_roles', 'is_enabled'])->orderBy('created_at')
                    )
                    ->schema([
                        Select::make('flow_key')
                            ->label(__('Flow'))
                            ->options(fn($livewire) => app(ApprovalRegistry::class)->all($livewire->tenant?->getKey()))
                            ->searchable()
                            ->required()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->hintIcon(Heroicon::OutlinedInformationCircle, __('Select the approval flow type, e.g. Sale, Purchase, Expense, etc.'))
                            ->columnSpan(2),

                        MoneyTextInput::make('min_amount')
                            ->currencyCode(fn(): string => filament()->getTenant()?->defaultCurrency?->code ?? 'USD')
                            ->placeholder('—')
                            ->hintIcon(Heroicon::OutlinedInformationCircle, __('Minimum document amount required for this approval.')),

                        Select::make('required_roles')
                            ->multiple()
                            ->searchable()
                            ->options(
                                fn() => Role::query()
                                    ->select(['name', 'display_name', 'description'])
                                    ->where('company_id', filament()->getTenant()->id)
                                    ->limit(config('dorsi.filament.select_filter_options_limit', 50))
                                    ->get()
                                    ->sortBy(function (Role $role) {
                                        return mb_strtolower(__($role->display_name ?? $role->name));
                                    }, SORT_NATURAL | SORT_FLAG_CASE)
                                    ->mapWithKeys(function (Role $role) {
                                        $display = '<strong>' . __($role->display_name ?? $role->name) . '</strong>';
                                        $description = __($role->description ?? '');

                                        return [$role->name => $display . ($description ? '<br>' . $description : '')];
                                    })
                                    ->toArray()
                            )
                            ->allowHtml()
                            ->hintIcon(Heroicon::OutlinedInformationCircle, __('Select one or more roles that must approve. Roles are managed in the user/role admin.'))
                            ->columnSpan(2),

                        Toggle::make('is_enabled')
                            ->label(__('Active'))
                            ->default(true)
                            ->inline(false)
                            ->hintIcon(Heroicon::OutlinedInformationCircle, __('Enable or disable this approval flow.')),
                    ])
                    ->columns(6)
                    ->reorderable(false)
                    ->addActionLabel(__('Add flow'))
                    ->columnSpanFull()
                    ->afterStateHydrated(function ($component, $state) {
                        // Validación de duplicados (solo hint visual)
                        $flows = collect($state);
                        $duplicates = $flows
                            ->groupBy(fn($item) => $item['flow_key'] . '-' . ($item['min_amount'] ?? '0'))
                            ->filter(fn($group) => $group->count() > 1);
                        if ($duplicates->isNotEmpty()) {
                            $component->addError('approvalSettings', __('Duplicate flows detected: same type and minimum amount!'));
                        }
                    }),
            ]);
    }

    /**
     * Widget/sección para mostrar historial de aprobaciones
     */
    private static function approvalHistorySection(): Section
    {
        return Section::make(__('Approval history'))
            ->schema([
                View::make('workflow::widgets.approval-history')
                    ->viewData(function (?Company $record): array {
                        dd($record);
                        if (! $record) {
                            return ['approvalHistory' => []];
                        }

                        $history = $record
                            ->approvalHistory()
                            ->orderByDesc('created_at')
                            ->limit(30)
                            ->get();

                        return ['approvalHistory' => $history];
                    })
                    ->columnSpanFull(),
            ])
            ->visible(fn(?Company $record) => $record->loadMissing('approvalHistory') && $record->approvalHistory->isNotEmpty())
            ->collapsible();
    }

    private static function brandingSection(): Section
    {
        return Section::make(__('Branding'))
            ->icon(Heroicon::OutlinedCubeTransparent)
            ->schema([
                FileUpload::make('logo_url')
                    ->label(__('Company logo'))
                    ->image()
                    ->disk(fn() => FileStoragePathService::getDisk(FileTypeEnum::CompanyLogos))
                    ->directory(fn($record) => FileStoragePathService::getPath(FileTypeEnum::CompanyLogos, $record))
                    ->visibility(fn() => FileStoragePathService::getVisibility(FileTypeEnum::CompanyLogos))
                    ->acceptedFileTypes(fn() => FileStoragePathService::getAcceptedTypes(FileTypeEnum::CompanyLogos))
                    ->maxSize(fn() => FileStoragePathService::getMaxSizeKb(FileTypeEnum::CompanyLogos))
                    ->hintIcon(Heroicon::OutlinedInformationCircle, __('Upload a high-quality logo. SVG, PNG, or WEBP recommended.'))
                    ->columnSpan(2),

                FileUpload::make('logo_pdf_url')
                    ->label(__('Company logo for PDF'))
                    ->helperText(__('Used on invoices, reports and documents generated in PDF'))
                    ->image()
                    ->disk(fn() => FileStoragePathService::getDisk(FileTypeEnum::CompanyLogos))
                    ->directory(fn($record) => FileStoragePathService::getPath(FileTypeEnum::CompanyLogos, $record))
                    ->visibility(fn() => FileStoragePathService::getVisibility(FileTypeEnum::CompanyLogos))
                    ->acceptedFileTypes(fn() => FileStoragePathService::getAcceptedTypes(FileTypeEnum::CompanyLogos))
                    ->maxSize(fn() => FileStoragePathService::getMaxSizeKb(FileTypeEnum::CompanyLogos))
                    ->hintIcon(Heroicon::OutlinedInformationCircle, __('Upload a high-quality logo. SVG, PNG, or WEBP recommended.'))
                    ->columnSpan(2),

                FileUpload::make('logo_isotype_url')
                    ->label(__('Company logo isotype'))
                    ->helperText(__('Used in the app header and small spaces. SVG, PNG, or WEBP recommended.'))
                    ->image()
                    ->disk(fn() => FileStoragePathService::getDisk(FileTypeEnum::CompanyLogos))
                    ->directory(fn($record) => FileStoragePathService::getPath(FileTypeEnum::CompanyLogos, $record))
                    ->visibility(fn() => FileStoragePathService::getVisibility(FileTypeEnum::CompanyLogos))
                    ->acceptedFileTypes(fn() => FileStoragePathService::getAcceptedTypes(FileTypeEnum::CompanyLogos))
                    ->maxSize(fn() => FileStoragePathService::getMaxSizeKb(FileTypeEnum::CompanyLogos))
                    ->hintIcon(Heroicon::OutlinedInformationCircle, __('Upload a lower-quality or simplified version of your logo for small spaces. SVG, PNG, or WEBP recommended.'))
                    ->columnSpan(2),
            ]);
    }

    private static function statusSection(): Section
    {
        return Section::make(__('Status'))
            ->schema([
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    private static function shouldDisableWhenBlank(string $statePath): callable
    {
        return fn(Get $get): bool => blank($get($statePath));
    }

    private function handleSriValidation(Set $set): void
    {
        $notifier = app(SriValidationNotifier::class);
        $ruc = $this->normalizeRuc((string) data_get($this, 'data.ruc'));

        if (! $this->hasValidRuc($ruc)) {
            $notifier->notifyInvalidRuc();

            return;
        }

        try {
            $validated = $this->hydrateSriDataFields(
                $ruc,
                function (string $field, mixed $value) use ($set): void {
                    $set('data.' . $field, $value, true, true);
                }
            );
        } catch (Throwable) {
            $notifier->notifyValidationFailure();

            return;
        }

        if ($validated) {
            $notifier->notifyValidated();

            return;
        }

        $notifier->notifyNoInformation();
    }

    private function syncCompanySubscriptionPlan(Company $company, mixed $subscriptionPlanState): void
    {
        $subscriptionPlan = $subscriptionPlanState instanceof SubscriptionPlanEnum
            ? $subscriptionPlanState->value
            : (string) $subscriptionPlanState;

        if (! in_array($subscriptionPlan, array_column(SubscriptionPlanEnum::cases(), 'value'), true)) {
            return;
        }

        $company->loadMissing('currentSubscription');

        $currentPlan = $company->currentSubscription?->plan_code;
        $currentPlan = $currentPlan instanceof SubscriptionPlanEnum
            ? $currentPlan->value
            : (filled($currentPlan) ? (string) $currentPlan : null);

        if ($currentPlan === $subscriptionPlan) {
            return;
        }

        if ($company->currentSubscription !== null) {
            $company->currentSubscription->update([
                'status' => 'ended',
                'ends_at' => now(),
            ]);
        }

        $company
            ->subscriptions()
            ->create([
                'plan_code' => $subscriptionPlan,
                'status' => 'active',
                'billing_cycle' => $company->currentSubscription?->billing_cycle ?? 'monthly',
                'starts_at' => now(),
                'ends_at' => null,
                'metadata' => [
                    'source' => 'edit_company_profile',
                ],
            ]);
    }

    private function sriPayloadMapper(): SriPayloadMapper
    {
        return app(SriPayloadMapper::class);
    }

    private function consultarContribuyente(string $ruc): array
    {
        return Cache::remember(
            "sri:contributor:{$ruc}",
            now()->addMinutes(10),
            function () use ($ruc): array {
                /** @var SriServiceInterface $sriService */
                $sriService = app(SriServiceInterface::class);

                return $sriService->consultarContribuyente($ruc);
            },
        );
    }

    private function hydrateSriDataFields(string $ruc, callable $set): bool
    {
        $data = $this->consultarContribuyente($ruc);

        if ($data === []) {
            return false;
        }

        $fields = app(SriPayloadHydrator::class)->hydrate($data);
        $fields['legal_representatives'] = $this->mapLegalRepresentatives($data);

        $hasPopulatedFields = false;

        foreach ($fields as $field => $value) {
            $hasPopulatedFields = $this->setWhenNotNull($set, $field, $value) || $hasPopulatedFields;
        }

        return $hasPopulatedFields;
    }

    private function setWhenNotNull(callable $set, string $field, mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $set($field, $value);

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{identification: string, name: string}>
     */
    private function mapLegalRepresentatives(array $data): array
    {
        $representatives = $this->sriPayloadMapper()->extractArrayValue($data, [
            'legal_representatives',
            'representantesLegales',
            'representantes_legales',
        ]);

        if (! is_array($representatives)) {
            return [];
        }

        if (
            array_key_exists('identificacion', $representatives)
            || array_key_exists('identification', $representatives)
            || array_key_exists('nombre', $representatives)
            || array_key_exists('name', $representatives)
        ) {
            $representatives = [$representatives];
        }

        return collect($representatives)
            ->filter(fn(mixed $representative): bool => is_array($representative))
            ->map(function (array $representative): array {
                return [
                    'identification' => mb_trim((string) (
                        $representative['identificacion']
                        ?? $representative['identification']
                        ?? $representative['numeroIdentificacion']
                        ?? $representative['numero_identificacion']
                        ?? $representative['ruc']
                        ?? $representative['cedula']
                        ?? ''
                    )),
                    'name' => mb_trim((string) (
                        $representative['nombre']
                        ?? $representative['name']
                        ?? $representative['razonSocial']
                        ?? $representative['razon_social']
                        ?? $representative['nombresApellidos']
                        ?? $representative['nombres_apellidos']
                        ?? ''
                    )),
                ];
            })
            ->filter(fn(array $representative): bool => $representative['identification'] !== '' || $representative['name'] !== '')
            ->values()
            ->all();
    }

    private function normalizeRuc(?string $value): string
    {
        return preg_replace('/\D/', '', $value ?? '') ?? '';
    }

    private function hasValidRuc(string $ruc): bool
    {
        return mb_strlen($ruc) === 13;
    }

    private function parseCertDate(?string $date): ?Carbon
    {
        if (is_string($date) && preg_match('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})Z$/', $date, $m)) {
            // yymmddhhmmssZ → 20yy-mm-dd hh:mm:ss
            $year = (int) $m[1];
            $year += ($year < 70) ? 2000 : 1900; // OpenSSL usa 2 dígitos, <70 es 2000+,>=70 es 1900+

            return Carbon::createFromFormat('YmdHis', sprintf('%04d%s%s%s%s%s', $year, $m[2], $m[3], $m[4], $m[5], $m[6]));
        }
        // Si ya es un formato compatible, intenta parsear normal
        try {
            return $date ? Carbon::parse($date) : null;
        } catch (Exception $e) {
            return null;
        }
    }
}
