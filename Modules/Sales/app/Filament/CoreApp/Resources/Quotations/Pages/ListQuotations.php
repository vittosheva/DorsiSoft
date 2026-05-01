<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Quotations\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sales\Filament\CoreApp\Resources\Quotations\QuotationResource;

final class ListQuotations extends BaseListRecords
{
    protected static string $resource = QuotationResource::class;
}
