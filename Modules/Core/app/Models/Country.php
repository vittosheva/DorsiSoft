<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class Country extends Model
{
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_countries';

    protected $fillable = [
        'name',
        'iso2',
        'iso3',
        'phone_code',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
