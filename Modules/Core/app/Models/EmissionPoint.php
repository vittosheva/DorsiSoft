<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;
use Modules\People\Models\User;
use Modules\People\Models\UserEmissionPoint;

final class EmissionPoint extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_emission_points';

    protected $fillable = [
        'company_id',
        'establishment_id',
        'code',
        'name',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_emission_points',
            'emission_point_id',
            'user_id'
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
}
