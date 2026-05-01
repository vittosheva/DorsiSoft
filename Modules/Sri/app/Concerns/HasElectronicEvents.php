<?php

declare(strict_types=1);

namespace Modules\Sri\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Sri\Models\SriElectronicEvent;

trait HasElectronicEvents
{
    public function electronicEvents(): MorphMany
    {
        return $this->morphMany(SriElectronicEvent::class, 'documentable');
    }
}
