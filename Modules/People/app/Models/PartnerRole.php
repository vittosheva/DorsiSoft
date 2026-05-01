<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\People\Enums\PartnerRoleEnum;
use Modules\People\Services\PartnerRoleLookup;

final class PartnerRole extends Model
{
    use HasFactory;

    protected $table = 'core_partner_roles';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'code' => PartnerRoleEnum::class,
        'is_active' => 'boolean',
    ];

    public function businessPartners(): BelongsToMany
    {
        return $this
            ->belongsToMany(BusinessPartner::class, 'core_business_partner_role', 'partner_role_id', 'business_partner_id')
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        self::saved(function (PartnerRole $role): void {
            $lookup = app(PartnerRoleLookup::class);

            $lookup->forget((string) $role->code);

            if ($role->wasChanged('code')) {
                $lookup->forget((string) $role->getOriginal('code'));
            }
        });

        self::deleted(function (PartnerRole $role): void {
            app(PartnerRoleLookup::class)->forget((string) $role->code);
        });
    }
}
