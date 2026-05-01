<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;

final class Establishment extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_establishments';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'address',
        'phone',
        'is_active',
        'is_main',
        'metadata',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_main' => 'boolean',
        'metadata' => 'array',
    ];

    public function emissionPoints(): HasMany
    {
        return $this->hasMany(EmissionPoint::class, 'establishment_id');
    }

    public function primaryEmissionPoint(): HasOne
    {
        return $this->hasOne(EmissionPoint::class, 'establishment_id');
    }
}
