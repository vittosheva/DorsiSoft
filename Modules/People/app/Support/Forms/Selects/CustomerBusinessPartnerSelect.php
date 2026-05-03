<?php

declare(strict_types=1);

namespace Modules\People\Support\Forms\Selects;

use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TableSelect;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\BusinessPartnerResource;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\Schemas\CustomerMinimalCreateForm;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\PartnerRole;
use Modules\People\Services\FinalConsumerRegistry;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\Tables\InvoiceCustomerPickerTable;
use RuntimeException;

final class CustomerBusinessPartnerSelect extends Select
{
    protected ?Closure $afterSelectionCallback = null;

    protected bool $fillFromRequest = true;

    protected string $requestKey = 'business_partner_id';

    protected bool $showFinalConsumer = true;

    protected int $initialResultsLimit = 10;

    protected int $searchResultsLimit = 10;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Customer'))
            ->prefixActions([
                Action::make('select_customer')
                    ->hiddenLabel()
                    ->icon(Heroicon::MagnifyingGlass)
                    ->tooltip(fn ($operation) => $operation !== 'view' ? __('Search customer') : null)
                    ->schema([
                        TableSelect::make('selected_customer')
                            ->hiddenLabel()
                            ->relationship('businessPartner')
                            ->tableConfiguration(InvoiceCustomerPickerTable::class),
                    ])
                    ->action(function (array $data, Set $set, Get $get): void {
                        $selectedId = $data['selected_customer'] ?? null;

                        if (blank($selectedId)) {
                            return;
                        }

                        $set($this->getName(), $selectedId);

                        if ($this->afterSelectionCallback) {
                            ($this->afterSelectionCallback)($selectedId, $set, $get);
                        }
                    })
                    ->modalHeading(__('Select customer'))
                    ->modalSubmitActionLabel(__('Confirm selection'))
                    ->visible(fn ($operation) => in_array($operation, ['create', 'edit'])),
            ])
            ->afterLabel([
                Action::make('final_consumer')
                    ->icon(Heroicon::User)
                    ->action(function (Set $set, Get $get): void {
                        $finalConsumer = app(FinalConsumerRegistry::class);
                        $companyId = Filament::getTenant()?->getKey();

                        if (! $finalConsumer->query($companyId)->exists($companyId)) {
                            $info = $finalConsumer->ensureExists($companyId);

                            Notification::make()
                                ->title(__('Final Consumer created successfully.'))
                                ->success()
                                ->send();
                        } else {
                            $info = $finalConsumer->query($companyId)->first();
                        }

                        $set($this->getName(), $info->id ?? null);
                    })
                    ->badge()
                    ->tooltip(__('Select final consumer'))
                    ->visible(fn ($operation) => $this->showFinalConsumer && in_array($operation, ['create', 'edit'])),
            ])
            ->placeholder(__('Search by name or identification...'))
            ->options(fn (): array => $this->resolveInitialOptions())
            ->searchable()
            ->required()
            ->getSearchResultsUsing(
                function (?string $search): array {
                    $search = mb_trim((string) $search);

                    $query = BusinessPartner::query()
                        ->select(['id', 'legal_name', 'identification_number']);

                    if ($search !== '') {
                        $query->where(function ($innerQuery) use ($search): void {
                            $searchPattern = '%'.$search.'%';

                            $innerQuery->whereLike('legal_name', $searchPattern)
                                ->orWhereLike('trade_name', $searchPattern)
                                ->orWhereLike('identification_number', $searchPattern);

                            if (mb_strlen($search) >= 3) {
                                $innerQuery->orWhereFullText(['legal_name', 'trade_name'], $search);
                            }
                        });
                    }

                    $limit = $search === ''
                        ? max(1, $this->initialResultsLimit)
                        : max(1, $this->searchResultsLimit);

                    return $query
                        ->orderBy('legal_name')
                        ->limit($limit)
                        ->get()
                        ->mapWithKeys(fn (BusinessPartner $businessPartner): array => [
                            $businessPartner->id => "{$businessPartner->legal_name} - {$businessPartner->identification_number}",
                        ])
                        ->all();
                }
            )
            ->getOptionLabelUsing(
                fn ($value): ?string => BusinessPartner::query()
                    ->whereKey($value)
                    ->value('legal_name')
            )
            ->live()
            ->preload()
            ->default(fn (): ?int => $this->resolveRequestedBusinessPartnerId())
            ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                if ($this->afterSelectionCallback) {
                    ($this->afterSelectionCallback)($state, $set, $get);
                }
            })
            ->createOptionAction(
                fn (Action $action) => $action
                    ->tooltip(__('Create :name', ['name' => __('Customer')]))
            )
            ->createOptionForm(fn (Schema $schema) => CustomerMinimalCreateForm::configure($schema))
            ->createOptionUsing(
                function (array $data): int {
                    return DB::transaction(function () use ($data): int {
                        unset($data['roles']);

                        $businessPartner = BusinessPartner::create($data);

                        $customerRoleId = PartnerRole::query()
                            ->where('code', PartnerRoleEnum::CUSTOMER)
                            ->value('id');

                        if (is_int($customerRoleId)) {
                            $businessPartner->roles()->sync([$customerRoleId]);
                        } else {
                            throw new RuntimeException(__('The CUSTOMER role was not found in the database.'));
                        }

                        return (int) $businessPartner->getKey();
                    });
                }
            )
            ->suffixActions([
                Action::make('edit_customer')
                    ->icon(Heroicon::PencilSquare)
                    ->tooltip(__('Edit :name', ['name' => __('Customer')]))
                    ->url(function (Get $get) {
                        if (! $get($this->getName())) {
                            return null;
                        }

                        return BusinessPartnerResource::getUrl('edit', ['record' => $get($this->getName())]);
                    }, shouldOpenInNewTab: true)
                    ->hidden(fn (Get $get, $operation) => blank($get($this->getName())) || $operation === 'view'),
            ]);
    }

    public static function getDefaultName(): ?string
    {
        return 'business_partner_id';
    }

    public function prefillFromRequest(string $requestKey = 'business_partner_id'): static
    {
        $this->fillFromRequest = true;
        $this->requestKey = $requestKey;

        return $this;
    }

    public function afterSelection(Closure $callback): static
    {
        $this->afterSelectionCallback = $callback;

        return $this;
    }

    public function initialResultsLimit(int $limit): static
    {
        $this->initialResultsLimit = max(1, $limit);

        return $this;
    }

    public function searchResultsLimit(int $limit): static
    {
        $this->searchResultsLimit = max(1, $limit);

        return $this;
    }

    public function showFinalConsumer(bool $condition = true): static
    {
        $this->showFinalConsumer = $condition;

        return $this;
    }

    private function resolveInitialOptions(): array
    {
        return BusinessPartner::query()
            ->select(['id', 'legal_name', 'identification_number'])
            ->orderBy('legal_name')
            ->limit(max(1, $this->initialResultsLimit))
            ->get()
            ->mapWithKeys(fn (BusinessPartner $businessPartner): array => [
                $businessPartner->id => "{$businessPartner->legal_name} - {$businessPartner->identification_number}",
            ])
            ->all();
    }

    private function resolveRequestedBusinessPartnerId(): ?int
    {
        if (! $this->fillFromRequest) {
            return null;
        }

        $businessPartnerId = request()->integer($this->requestKey);

        if ($businessPartnerId < 1) {
            return null;
        }

        return BusinessPartner::query()
            ->whereKey($businessPartnerId)
            ->value('id');
    }
}
