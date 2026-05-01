<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Enums\SubscriptionPlanEnum;
use Modules\Core\Models\Traits\HasTenancy;

final class CompanySubscription extends Model
{
    use HasFactory;
    use HasTenancy;
    use Userstamps;

    protected $table = 'core_company_subscriptions';

    protected $fillable = [
        'company_id',
        'plan_code',
        'status',
        'billing_cycle',
        'starts_at',
        'ends_at',
        'metadata',
    ];

    protected $casts = [
        'plan_code' => SubscriptionPlanEnum::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'array',
    ];
}
