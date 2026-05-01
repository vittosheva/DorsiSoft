<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class City extends Model
{
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_cities';

    protected $fillable = [
        'state_id',
        'name',
        'latitude',
        'longitude',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function parishes(): HasMany
    {
        return $this->hasMany(Parish::class);
    }
}
