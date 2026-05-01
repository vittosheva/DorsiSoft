<?php

declare(strict_types=1);

namespace Modules\Core\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Core\Models\Company;
use Modules\Core\Models\Establishment;
use Modules\Core\Models\Scopes\TenantScope;
use Modules\Core\Services\EstablishmentSyncService;
use Modules\Core\Services\EstablishmentValidationService;
use Modules\Core\Support\Forms\TextInputs\ThreeDigitCodeTextInput;
use Modules\Core\Support\Sri\SriPayloadMapper;
use Modules\Sri\Services\Sri\Contracts\SriServiceInterface;
use Throwable;

final class CompanyEstablishmentsManager extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public int $companyId;

    public ?array $data = [];

    public function mount(int $companyId): void
    {
        $this->companyId = $companyId;

        $this->establishmentsForm->fill([
            'establishments' => $this->loadEstablishments(),
        ]);
    }

    public function establishmentsForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading(__('Company establishments'))
                    ->headerActions([
                        Action::make('fetchSriEstablishments')
                            ->label(__('Fetch in SRI'))
                            ->icon(Heroicon::ArrowPath)
                            ->size(Size::ExtraSmall)
                            ->color(Color::Indigo)
                            ->requiresConfirmation(function (): bool {
                                $establishments = $this->data['establishments'] ?? [];

                                if (! is_array($establishments) || $establishments === []) {
                                    return false;
                                }

                                return collect($establishments)->contains(function ($establishment): bool {
                                    if (! is_array($establishment)) {
                                        return false;
                                    }

                                    return filled($establishment['id'] ?? null)
                                        || filled($establishment['establishment_code'] ?? null)
                                        || filled($establishment['emission_point_code'] ?? null);
                                });
                            })
                            ->action(function (): void {
                                $ruc = preg_replace('/\D/', '', Company::query()->select(['id', 'ruc'])->find($this->companyId)?->ruc ?? '') ?? '';

                                if (mb_strlen($ruc) !== 13) {
                                    Notification::make()
                                        ->title(__('Save the company profile with a valid RUC before fetching establishments.'))
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                try {
                                    $rows = $this->fetchSriEstablishments($ruc);
                                    $mappedEstablishments = $this->mapSriEstablishments($rows);

                                    if ($mappedEstablishments === []) {
                                        Notification::make()
                                            ->title(__('No establishments found in the SRI.'))
                                            ->warning()
                                            ->send();

                                        return;
                                    }

                                    $this->establishmentsForm->fill([
                                        'establishments' => array_values($mappedEstablishments),
                                    ]);

                                    Notification::make()
                                        ->title(__('SRI establishments loaded successfully'))
                                        ->info()
                                        ->send();
                                } catch (Throwable) {
                                    Notification::make()
                                        ->title(__('Unable to fetch establishments from the SRI.'))
                                        ->danger()
                                        ->send();
                                }
                            }),

                        Action::make('toggleAllAdditionalEstablishmentFields')
                            ->label(__('Show/hide additional fields'))
                            ->icon(Heroicon::AdjustmentsHorizontal)
                            ->size(Size::ExtraSmall)
                            ->color('gray')
                            ->visible(function (): bool {
                                $establishments = $this->data['establishments'] ?? [];

                                return is_array($establishments) && $establishments !== [];
                            })
                            ->action(function (): void {
                                $establishments = $this->data['establishments'] ?? [];

                                if (! is_array($establishments) || $establishments === []) {
                                    return;
                                }

                                $shouldOpenAll = ! collect($establishments)
                                    ->every(fn ($establishment): bool => (bool) ($establishment['show_more_fields'] ?? false));

                                foreach ($establishments as $key => $establishment) {
                                    if (! is_array($establishment)) {
                                        continue;
                                    }

                                    $establishments[$key]['show_more_fields'] = $shouldOpenAll;
                                }

                                $this->data['establishments'] = $establishments;
                            }),

                        Action::make('save')
                            ->label(__('Save establishments'))
                            ->icon(Heroicon::CursorArrowRays)
                            ->size(Size::ExtraSmall)
                            ->color(Color::Green)
                            ->action(fn () => $this->saveEstablishments()),
                    ])
                    ->schema([
                        Repeater::make('establishments')
                            ->hiddenLabel()
                            ->itemLabel(function (array $state): ?string {
                                $parts = array_filter([
                                    filled($state['establishment_code'] ?? null) && filled($state['emission_point_code'] ?? null)
                                        ? ($state['establishment_code'].'-'.$state['emission_point_code'])
                                        : null,
                                    filled($state['name'] ?? null)
                                        ? (string) $state['name']
                                        : null,
                                ]);

                                if ($parts === []) {
                                    return 'New establishment';
                                }

                                return implode(' · ', $parts);
                            })
                            ->schema([
                                Hidden::make('id'),
                                Hidden::make('name_source')
                                    ->default('manual'),
                                ThreeDigitCodeTextInput::make('establishment_code')
                                    ->required()
                                    ->autofocus()
                                    ->columnSpan(3),
                                ThreeDigitCodeTextInput::make('emission_point_code')
                                    ->required()
                                    ->columnSpan(3),
                                Toggle::make('is_main')
                                    ->inline(false)
                                    ->default(false)
                                    ->fixIndistinctState()
                                    ->distinct()
                                    ->columnSpan(2),
                                Toggle::make('is_active')
                                    ->inline(false)
                                    ->default(true)
                                    ->columnSpan(2),
                                Toggle::make('show_more_fields')
                                    ->inline(false)
                                    ->default(true)
                                    ->dehydrated(false)
                                    ->columnSpan(2),
                                Section::make()
                                    ->hiddenLabel()
                                    ->schema([
                                        Textarea::make('address')
                                            ->maxLength(255)
                                            ->rows(3),
                                        TextInput::make('name')
                                            ->required(fn (Get $get): bool => ($get('name_source') ?? 'manual') === 'manual')
                                            ->afterStateUpdatedJs(<<<'JS'
                                                if (($state ?? '').trim() !== '') {
                                                    $set('name_source', 'manual')
                                                }
                                            JS)
                                            ->maxLength(255),
                                        TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(255),
                                    ])
                                    ->visibleJs(<<<'JS'
                                        !! $get('show_more_fields')
                                    JS)
                                    ->columnSpanFull()
                                    ->columns(3),
                            ])
                            ->minItems(0)
                            ->rules([
                                fn () => app(EstablishmentValidationService::class)->makeRepeaterRule(),
                            ])
                            ->collapsed()
                            ->columns(12)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data')
            ->model(Company::class);
    }

    public function saveEstablishments(): void
    {
        $data = $this->establishmentsForm->getState();
        $company = Company::find($this->companyId);

        if (! $company instanceof Company) {
            return;
        }

        app(EstablishmentSyncService::class)->sync($company, $data['establishments'] ?? [], 'edit_company_profile');

        Notification::make()
            ->title(__('Company establishments saved'))
            ->success()
            ->send();

        $this->establishmentsForm->fill([
            'establishments' => $this->loadEstablishments(),
        ]);
    }

    public function render(): View
    {
        return view('core::livewire.company-establishments-manager');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadEstablishments(): array
    {
        $company = Company::with([
            'establishments' => fn ($query) => $query
                ->withoutGlobalScope(TenantScope::class)
                ->where('company_id', $this->companyId)
                ->with([
                    'primaryEmissionPoint' => fn ($q) => $q
                        ->withoutGlobalScope(TenantScope::class)
                        ->select(['id', 'establishment_id', 'code', 'is_default', 'is_active']),
                ]),
        ])->find($this->companyId);

        if (! $company instanceof Company) {
            return [];
        }

        $rows = $company->establishments
            ->map(function (Establishment $establishment): array {
                $emissionPoint = $establishment->primaryEmissionPoint;
                $name = mb_trim((string) ($establishment->name ?? ''));
                $nameSource = (string) data_get($establishment->metadata, 'name_source', 'manual');

                if ($name === '' || Str::lower($name) === Str::lower(sprintf('Establishment %s', (string) ($establishment->code ?? '')))) {
                    $name = 'N/A';
                    $nameSource = 'fallback';
                }

                return [
                    'id' => $establishment->getKey(),
                    'establishment_code' => (string) ($establishment->code ?? ''),
                    'emission_point_code' => (string) ($emissionPoint?->code ?? ''),
                    'name' => $name,
                    'name_source' => $nameSource,
                    'address' => $establishment->address,
                    'phone' => $establishment->phone,
                    'is_main' => (bool) ($establishment->is_main ?? false),
                    'is_active' => (bool) ($establishment->is_active ?? true),
                    'show_more_fields' => false,
                ];
            })
            ->values()
            ->all();

        $hasMain = collect($rows)->contains(fn (array $row): bool => $row['is_main'] === true);

        if (! $hasMain && $rows !== []) {
            $rows[0]['is_main'] = true;
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<string, mixed>>
     */
    private function mapSriEstablishments(array $rows): array
    {
        $mapped = $this->sriPayloadMapper()->mapEstablishments(
            rows: $rows,
            keyResolver: fn (array $item): string => $item['establishment_code'],
        );

        if (! is_array($mapped)) {
            return [];
        }

        foreach ($mapped as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = isset($item['name']) ? mb_trim((string) $item['name']) : '';

            if ($name === '') {
                $item['name'] = 'N/A';
                $item['name_source'] = 'fallback';
            } else {
                $item['name_source'] = 'sri';
            }

            $mapped[$key] = $item;
        }

        return $mapped;
    }

    private function fetchSriEstablishments(string $ruc): array
    {
        return Cache::remember(
            "sri:establishments:{$ruc}",
            now()->addMinutes(5),
            function () use ($ruc): array {
                /** @var SriServiceInterface $sriService */
                $sriService = app(SriServiceInterface::class);

                return $sriService->consultarEstablecimientosPorRuc($ruc);
            },
        );
    }

    private function sriPayloadMapper(): SriPayloadMapper
    {
        return app(SriPayloadMapper::class);
    }
}
