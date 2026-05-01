<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Accounting\Enums\AccountNatureEnum;
use Modules\Accounting\Enums\AccountTypeEnum;
use Modules\Core\Models\Company;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;

final class ChartOfAccount extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'fin_chart_of_accounts';

    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'type',
        'nature',
        'level',
        'is_control',
        'allows_entries',
        'is_active',
        'sri_code',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountTypeEnum::class,
            'nature' => AccountNatureEnum::class,
            'level' => 'integer',
            'is_control' => 'boolean',
            'allows_entries' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    public function accountBalances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'account_id');
    }

    public function isDebitNature(): bool
    {
        return $this->nature === AccountNatureEnum::Debit;
    }

    public function isCreditNature(): bool
    {
        return $this->nature === AccountNatureEnum::Credit;
    }

    public function canReceiveEntries(): bool
    {
        return $this->allows_entries && ! $this->is_control && $this->is_active;
    }

    #[Scope]
    public function leafAccounts(Builder $query): void
    {
        $query->where('allows_entries', true)->where('is_control', false);
    }

    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    public function byType(Builder $query, AccountTypeEnum $type): void
    {
        $query->where('type', $type->value);
    }

    #[Scope]
    public function roots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }
}
