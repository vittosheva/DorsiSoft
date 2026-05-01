<?php

declare(strict_types=1);

namespace Modules\Sri\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Sri\Models\SriTechnicalExchange;

trait HasSriTechnicalExchanges
{
    public function technicalExchanges(): MorphMany
    {
        return $this->morphMany(SriTechnicalExchange::class, 'documentable');
    }
}
