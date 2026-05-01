<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\City;
use Modules\Core\Models\Country;
use Modules\Core\Models\Parish;
use Modules\Core\Models\State;
use Modules\Core\Models\Traits\ForceDeleteSoftDeletedDuplicates;
use Modules\Core\Models\Traits\HasActiveScope;
use Modules\Core\Models\Traits\HasAutoCode;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Codes\CodeGenerator;
use Modules\Core\Support\Models\BaseModel;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\People\Services\FinalConsumerRegistry;
use Modules\People\Services\PartnerRoleLookup;

final class BusinessPartner extends BaseModel
{
    use ForceDeleteSoftDeletedDuplicates;
    use HasActiveScope;
    use HasAutoCode;
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_business_partners';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'code',
        'identification_type',
        'identification_number',
        'legal_name',
        'trade_name',
        'email',
        'phone',
        'mobile',
        'tax_address',
        'country_id',
        'state_id',
        'city_id',
        'parish_id',
        'is_active',
        'metadata',
        'ocr_document_path',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'email' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public static function getUniqueConstraintColumns(): array
    {
        return ['company_id', 'identification_type', 'identification_number'];
    }

    public static function getCodePrefix(): string
    {
        return 'PER';
    }

    public static function getCodeScope(): array
    {
        return [];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'parish_id');
    }

    public function roles(): BelongsToMany
    {
        return $this
            ->belongsToMany(PartnerRole::class, 'core_business_partner_role', 'business_partner_id', 'partner_role_id')
            ->withTimestamps();
    }

    public function customerDetail(): HasOne
    {
        return $this->hasOne(CustomerDetail::class, 'business_partner_id');
    }

    public function supplierDetail(): HasOne
    {
        return $this->hasOne(SupplierDetail::class, 'business_partner_id');
    }

    public function carrierDetail(): HasOne
    {
        return $this->hasOne(CarrierDetail::class, 'business_partner_id');
    }

    public function carrierVehicles(): HasMany
    {
        return $this->hasMany(CarrierVehicle::class, 'business_partner_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(PartnerAddress::class, 'business_partner_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(PartnerBankAccount::class, 'business_partner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'business_partner_id');
    }

    #[Scope]
    public function byRole(Builder $query, PartnerRoleEnum|string $roleCode): void
    {
        if ($roleCode instanceof PartnerRoleEnum) {
            $roleCode = $roleCode->value;
        }
        $roleId = app(PartnerRoleLookup::class)->idFor($roleCode);

        if ($roleId === null) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereExists(function (\Illuminate\Database\Query\Builder $q) use ($roleId): void {
            $q->selectRaw('1')
                ->from('core_business_partner_role as cbpr')
                ->whereColumn('cbpr.business_partner_id', 'core_business_partners.id')
                ->where('cbpr.partner_role_id', $roleId);
        });
    }

    protected static function booted(): void
    {
        self::deleted(function (BusinessPartner $partner): void {
            $registry = app(FinalConsumerRegistry::class);

            if (! $registry->isFinalConsumer($partner)) {
                return;
            }

            // Mark the business partner as inactive when deleting
            $partner->is_active = false;
            $partner->saveQuietly();

            $registry->forgetCache($partner->company_id);
        });

        self::restored(function (BusinessPartner $partner): void {
            if (blank($partner->code)) {
                $partner->updateQuietly([
                    'code' => CodeGenerator::next(
                        modelClass: self::class,
                        prefix: self::getCodePrefix(),
                        padding: 3,
                        scope: ['company_id' => $partner->company_id],
                    ),
                ]);
            }
        });
    }

    #[Scope]
    protected function customers(Builder $query): void
    {
        $this->byRole($query, PartnerRoleEnum::CUSTOMER->value);
    }

    #[Scope]
    protected function suppliers(Builder $query): void
    {
        $this->byRole($query, PartnerRoleEnum::SUPPLIER->value);
    }

    #[Scope]
    protected function carriers(Builder $query): void
    {
        $this->byRole($query, PartnerRoleEnum::CARRIER->value);
    }
}
