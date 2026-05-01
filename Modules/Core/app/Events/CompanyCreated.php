<?php

declare(strict_types=1);

namespace Modules\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Models\Company;

final class CompanyCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Company $company) {}
}
