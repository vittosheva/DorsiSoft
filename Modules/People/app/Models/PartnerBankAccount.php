<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\People\Enums\BankAccountTypeEnum;

final class PartnerBankAccount extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_partner_bank_accounts';

    protected $fillable = [
        'business_partner_id',
        'bank_name',
        'account_type',
        'account_number',
        'account_holder',
        'identification',
        'swift_code',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'account_type' => BankAccountTypeEnum::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    protected static function booted(): void
    {
        self::saving(function (self $account): void {
            if (! $account->is_default) {
                return;
            }

            $account->is_active = true;

            self::query()
                ->where('business_partner_id', $account->business_partner_id)
                ->when(
                    $account->exists,
                    fn (Builder $query): Builder => $query->whereKeyNot($account->getKey()),
                )
                ->update(['is_default' => false]);
        });
    }
}
