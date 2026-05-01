<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\Invoices\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sales\Filament\CoreApp\Resources\Invoices\InvoiceResource;

final class ListInvoices extends BaseListRecords
{
    protected static string $resource = InvoiceResource::class;
}
