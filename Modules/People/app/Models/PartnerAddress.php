<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\City;
use Modules\People\Enums\AddressTypeEnum;

final class PartnerAddress extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_partner_addresses';

    protected $fillable = [
        'business_partner_id',
        'type',
        'address',
        'reference',
        'city_id',
        'postal_code',
        'phone',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AddressTypeEnum::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    protected static function booted(): void
    {
        self::saving(function (self $address): void {
            if (! $address->is_default) {
                return;
            }

            $address->is_active = true;

            self::query()
                ->where('business_partner_id', $address->business_partner_id)
                ->when(
                    $address->exists,
                    fn (Builder $query): Builder => $query->whereKeyNot($address->getKey()),
                )
                ->update(['is_default' => false]);
        });
    }
}
