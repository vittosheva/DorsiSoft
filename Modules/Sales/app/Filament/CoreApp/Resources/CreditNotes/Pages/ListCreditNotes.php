<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\CreditNotes\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sales\Filament\CoreApp\Resources\CreditNotes\CreditNoteResource;

final class ListCreditNotes extends BaseListRecords
{
    protected static string $resource = CreditNoteResource::class;
}
