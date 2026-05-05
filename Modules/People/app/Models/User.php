<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Enums\LanguageEnum;
use Modules\Core\Models\Company;
use Modules\Core\Models\CompanyUser;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\Establishment;
use Modules\Core\Models\Traits\HasAutoCode;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

final class User extends BaseAuthenticatable implements FilamentUser, HasAvatar, HasDefaultTenant, HasTenants
{
    use HasApiTokens;
    use HasAutoCode;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use HasTenancy;
    use Notifiable;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'business_partner_id',
        'code',
        'name',
        'email',
        'phone',
        'language',
        'timezone',
        'avatar_url',
        'establishment_id',
        'email_verified_at',
        'is_allowed_to_login',
        'is_active',
        'password',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'language' => LanguageEnum::class,
        'is_allowed_to_login' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function getCodePrefix(): string
    {
        return 'USR';
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'core_company_user', 'user_id', 'company_id')
            ->using(CompanyUser::class);
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function customerDetails(): HasMany
    {
        return $this->hasMany(CustomerDetail::class, 'seller_id');
    }

    public function userEmissionPoints(): HasMany
    {
        return $this->hasMany(UserEmissionPoint::class, 'user_id');
    }

    public function emissionPoints(): BelongsToMany
    {
        return $this->belongsToMany(
            EmissionPoint::class,
            'user_emission_points',
            'user_id',
            'emission_point_id'
        )
            ->using(UserEmissionPoint::class)
            ->withPivot([
                'is_default',
                'payment_method_id',
                'cash_register_id',
                'allow_mixed_payments',
                'restrict_payment_methods',
                'require_shift',
            ])
            ->withTimestamps();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->getCachedCompanies();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        $tenantKey = session()->get("filament.tenant.{$panel->getId()}");
        $companies = $this->getCachedCompanies();

        if (blank($tenantKey)) {
            return $companies->first();
        }

        return $companies->find($tenantKey) ?? $companies->first();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->getCachedCompanies()->contains($tenant);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $base = $this->hasVerifiedEmail() && $this->is_allowed_to_login && $this->is_active;

        if ($panel->getId() === 'system-admin') {
            return $base && $this->hasRole('superadmin');
        }

        return $base;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url !== null
            ? Storage::url($this->avatar_url)
            : $this->avatar_url;
    }

    /**
     * Retorna el prefijo de código para autogeneración.
     */
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    private function getCachedCompanies(): Collection
    {
        return once(fn () => $this->companies()->get());
    }
}
