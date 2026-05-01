<?php

declare(strict_types=1);

namespace Modules\Sales\Filament\CoreApp\Resources\DebitNotes\Pages;

use Modules\Core\Support\Pages\BaseListRecords;
use Modules\Sales\Filament\CoreApp\Resources\DebitNotes\DebitNoteResource;

final class ListDebitNotes extends BaseListRecords
{
    protected static string $resource = DebitNoteResource::class;
}
