<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

final class CompanyUser extends Pivot
{
    protected $table = 'core_company_user';
}
