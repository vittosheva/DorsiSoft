<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Withholdings\Pages;

use Modules\Core\Support\Pages\BaseCreateRecord;
use Modules\Sales\Filament\CoreApp\Resources\Withholdings\WithholdingResource;

final class CreateWithholding extends BaseCreateRecord
{
    protected static string $resource = WithholdingResource::class;
}
