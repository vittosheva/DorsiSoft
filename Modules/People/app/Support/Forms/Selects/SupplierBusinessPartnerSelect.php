<?php

declare(strict_types=1);

namespace Modules\People\Support\Forms\Selects;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TableSelect;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\BusinessPartnerResource;
use Modules\People\Filament\CoreApp\Resources\BusinessPartners\Schemas\SupplierMinimalCreateForm;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\PartnerRole;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\Tables\WithholdingSupplierPickerTable;
use RuntimeException;

final class SupplierBusinessPartnerSelect extends Select
{
    protected ?Closure $afterSelectionCallback = null;

    protected bool $fillFromRequest = true;

    protected string $requestKey = 'business_partner_id';

    protected int $initialResultsLimit = 10;

    protected int $searchResultsLimit = 10;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Supplier'))
            ->prefixActions([
                Action::make('select_supplier')
                    ->hiddenLabel()
                    ->icon(Heroicon::MagnifyingGlass)
                    ->tooltip(fn ($operation) => $operation !== 'view' ? __('Search supplier') : null)
                    ->schema([
                        TableSelect::make('selected_supplier')
                            ->hiddenLabel()
                            ->relationship('businessPartner')
                            ->tableConfiguration(WithholdingSupplierPickerTable::class),
                    ])
                    ->action(function (array $data, Set $set, Get $get): void {
                        $selectedId = $data['selected_supplier'] ?? null;

                        if (blank($selectedId)) {
                            return;
                        }

                        $set($this->getName(), $selectedId);

                        if ($this->afterSelectionCallback) {
                            ($this->afterSelectionCallback)($selectedId, $set, $get);
                        }
                    })
                    ->modalHeading(__('Select supplier'))
                    ->modalSubmitActionLabel(__('Confirm selection'))
                    ->visible(fn ($operation) => in_array($operation, ['create', 'edit'])),
            ])
            ->placeholder(__('Search by name or identification...'))
            ->options(fn (): array => $this->resolveInitialOptions())
            ->searchable()
            ->required()
            ->getSearchResultsUsing(
                function (?string $search): array {
                    $search = mb_trim((string) $search);

                    $query = BusinessPartner::query()
                        ->select(['id', 'legal_name', 'identification_number'])
                        ->suppliers();

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
                    ->tooltip(__('Create :name', ['name' => __('Supplier')]))
            )
            ->createOptionForm(fn (Schema $schema) => SupplierMinimalCreateForm::configure($schema))
            ->createOptionUsing(
                function (array $data): int {
                    return DB::transaction(function () use ($data): int {
                        unset($data['roles']);

                        $businessPartner = BusinessPartner::create($data);

                        $supplierRoleId = PartnerRole::query()
                            ->where('code', PartnerRoleEnum::SUPPLIER)
                            ->value('id');

                        if (is_int($supplierRoleId)) {
                            $businessPartner->roles()->sync([$supplierRoleId]);
                        } else {
                            throw new RuntimeException(__('The SUPPLIER role was not found in the database.'));
                        }

                        return (int) $businessPartner->getKey();
                    });
                }
            )
            ->suffixActions([
                Action::make('edit_supplier')
                    ->icon(Heroicon::PencilSquare)
                    ->tooltip(__('Edit :name', ['name' => __('Supplier')]))
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

    private function resolveInitialOptions(): array
    {
        return BusinessPartner::query()
            ->select(['id', 'legal_name', 'identification_number'])
            ->suppliers()
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
            // ->customers()
            ->whereKey($businessPartnerId)
            ->value('id');
    }
}
