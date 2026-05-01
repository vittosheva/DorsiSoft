<?php

declare(strict_types=1);

namespace Modules\People\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\People\Models\BusinessPartner;

final class FinalConsumerRegistry
{
    public const IDENTIFICATION_TYPE = 'final_consumer';

    public const IDENTIFICATION_NUMBER = '9999999999999';

    public function __construct(private readonly PartnerRoleLookup $roleLookup) {}

    public function cacheKey(int $companyId): string
    {
        return "final_consumer_exists.{$companyId}";
    }

    public function exists(int $companyId): bool
    {
        if ($companyId <= 0) {
            return false;
        }

        return Cache::remember($this->cacheKey($companyId), now()->addHours(6), fn (): bool => $this->query($companyId)->exists());
    }

    public function ensureExists(int $companyId): BusinessPartner
    {
        if ($companyId <= 0) {
            throw new InvalidArgumentException(__('Company id is required to resolve the final consumer.'));
        }

        $partner = BusinessPartner::withTrashed()
            ->where('company_id', $companyId)
            ->where('identification_type', self::IDENTIFICATION_TYPE)
            ->where('identification_number', self::IDENTIFICATION_NUMBER)
            ->first();

        if ($partner) {
            if ($partner->trashed()) {
                $partner->restore();
            }

            return $partner;
        }

        $partner = BusinessPartner::create([
            'company_id' => $companyId,
            'identification_type' => self::IDENTIFICATION_TYPE,
            'identification_number' => self::IDENTIFICATION_NUMBER,
            'legal_name' => __('Final Consumer'),
            'is_active' => true,
        ]);

        $customerRoleId = $this->roleLookup->idFor(PartnerRoleEnum::CUSTOMER->value);

        if ($customerRoleId) {
            $partner->roles()->attach($customerRoleId);
        }

        return $partner;
    }

    public function forgetCache(int $companyId): void
    {
        Cache::forget($this->cacheKey($companyId));
    }

    public function isFinalConsumer(BusinessPartner $partner): bool
    {
        return $partner->identification_type === self::IDENTIFICATION_TYPE
            && $partner->identification_number === self::IDENTIFICATION_NUMBER;
    }

    public function query(int $companyId): Builder
    {
        return BusinessPartner::query()
            ->where('company_id', $companyId)
            ->where('identification_type', self::IDENTIFICATION_TYPE)
            ->where('identification_number', self::IDENTIFICATION_NUMBER);
    }
}
