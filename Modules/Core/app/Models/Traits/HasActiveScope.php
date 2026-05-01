<?php

declare(strict_types=1);

namespace Modules\Core\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

trait HasActiveScope
{
    #[Scope]
    public function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
