<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Events\CompanyCreated;
use Modules\Finance\Models\Tax;
use Modules\People\Models\BusinessPartner;
use Modules\People\Models\User;
use Modules\Sri\Enums\SriEnvironmentEnum;
use Modules\Workflow\Models\ApprovalRecord;
use Modules\Workflow\Models\WorkflowApprovalSetting;

final class Company extends Model implements HasAvatar, HasCurrentTenantLabel, HasName
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => CompanyCreated::class,
    ];

    protected $table = 'core_companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'legal_name',
        'trade_name',
        'ruc',
        'is_accounting_required',
        'is_special_taxpayer',
        'special_taxpayer_resolution',
        'tax_regime',
        'contributor_status',
        'taxpayer_type',
        'contributor_category',
        'is_withholding_agent',
        'is_shell_company',
        'has_nonexistent_transactions',
        'started_activities_at',
        'ceased_activities_at',
        'restarted_activities_at',
        'sri_updated_at',
        'legal_representatives',
        'suspension_cancellation_reason',
        'rimpe_expires_at',
        'economic_activity_code',
        'business_activity',
        'tax_address',
        'country_id',
        'state_id',
        'city_id',
        'parish',
        'phone',
        'email',
        'website',
        'avatar_url',
        'default_currency_id',
        'timezone',
        'default_tax_id',
        'sri_environment',
        'certificate_path',
        'certificate_password_encrypted',
        'certificate_valid_from',
        'certificate_expiration_date',
        'logo_url',
        'logo_pdf_url',
        'logo_isotype_url',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_accounting_required' => 'boolean',
        'is_special_taxpayer' => 'boolean',
        'is_withholding_agent' => 'boolean',
        'is_shell_company' => 'boolean',
        'has_nonexistent_transactions' => 'boolean',
        'is_active' => 'boolean',
        'rimpe_expires_at' => 'date',
        'started_activities_at' => 'datetime',
        'ceased_activities_at' => 'datetime',
        'restarted_activities_at' => 'datetime',
        'sri_updated_at' => 'datetime',
        'legal_representatives' => 'array',
        'sri_environment' => SriEnvironmentEnum::class,
        'certificate_valid_from' => 'date',
        'certificate_expiration_date' => 'date',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'core_company_user', 'company_id', 'user_id');
    }

    public function establishments(): HasMany
    {
        return $this->hasMany(Establishment::class, 'company_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CompanySubscription::class, 'company_id');
    }

    public function businessPartners(): HasMany
    {
        return $this->hasMany(BusinessPartner::class, 'company_id');
    }

    public function currentSubscription(): HasOne
    {
        return $this
            ->hasOne(CompanySubscription::class, 'company_id')->ofMany([
                'starts_at' => 'max',
                'id' => 'max',
            ], function (Builder $query): void {
                $query
                    ->whereIn('status', ['trial', 'active'])
                    ->where('starts_at', '<=', now())
                    ->where(function (Builder $builder): void {
                        $builder
                            ->whereNull('ends_at')
                            ->orWhere('ends_at', '>=', now());
                    });
            });
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function defaultCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'default_currency_id');
    }

    public function defaultTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'default_tax_id');
    }

    public function approvalHistory()
    {
        return $this->hasMany(ApprovalRecord::class, 'company_id');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (blank($this->logo_isotype_url)) {
            return null;
        }

        return asset($this->logo_isotype_url);
    }

    public function getFilamentName(): string
    {
        return $this->legal_name;
    }

    public function approvalSettings(): HasMany
    {
        return $this->hasMany(WorkflowApprovalSetting::class, 'company_id');
    }

    public function getCurrentTenantLabel(): string
    {
        return __('Active company:');
    }
}
