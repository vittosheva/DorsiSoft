<?php

declare(strict_types=1);

namespace Modules\People\Support\Forms\Selects;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Modules\People\Enums\RoleEnum;
use Modules\People\Filament\CoreApp\Resources\Users\UserResource;
use Modules\People\Models\User;

final class SellerUserSelect extends Select
{
    protected bool $fillFromRequest = false;

    protected string $requestKey = 'seller_id';

    protected bool $hydrateSellerSnapshot = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Seller'))
            ->options(fn (): array => $this->resolveSellerOptions())
            ->getSearchResultsUsing(fn (?string $search): array => $this->resolveSellerOptions($search))
            ->getOptionLabelUsing(fn (mixed $value): ?string => User::query()->whereKey($value)->value('name'))
            ->searchable()
            ->nullable()
            ->prefixIcon(UserResource::getNavigationIcon())
            ->live()
            ->default(fn (): ?int => $this->resolveRequestedSellerId())
            ->afterStateHydrated(fn ($state, Set $set): bool => $this->hydrateSnapshot($state, $set))
            ->afterStateUpdated(fn ($state, Set $set): bool => $this->hydrateSnapshot($state, $set));
    }

    public static function getDefaultName(): ?string
    {
        return 'seller_id';
    }

    public function prefillFromRequest(string $requestKey = 'seller_id'): static
    {
        $this->fillFromRequest = true;
        $this->requestKey = $requestKey;

        return $this;
    }

    public function withSellerSnapshot(bool $condition = true): static
    {
        $this->hydrateSellerSnapshot = $condition;

        return $this;
    }

    private function resolveRequestedSellerId(): ?int
    {
        if (! $this->fillFromRequest) {
            return null;
        }

        $sellerId = request()->integer($this->requestKey);

        if ($sellerId < 1) {
            return null;
        }

        return User::query()
            ->whereKey($sellerId)
            ->value('id');
    }

    private function hydrateSnapshot(mixed $state, Set $set): bool
    {
        if (! $this->hydrateSellerSnapshot) {
            return false;
        }

        if (blank($state)) {
            $set('seller_name', null);

            return false;
        }

        $seller = User::query()->select(['name'])->find($state);

        if (! $seller) {
            return false;
        }

        $set('seller_name', $seller->name);

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function resolveSellerOptions(?string $search = null): array
    {
        $selectedSellerId = $this->getState();

        $sellerIds = User::query()
            ->role(RoleEnum::SALES_REP->value)
            ->pluck('id')
            ->all();

        return User::query()
            ->when(
                $selectedSellerId,
                fn ($query) => $query->whereIn('id', $sellerIds)->orWhere('id', $selectedSellerId),
                fn ($query) => $query->whereIn('id', $sellerIds),
            )
            ->when(
                filled($search),
                fn ($query) => $query->where('name', 'like', mb_trim((string) $search).'%'),
            )
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
