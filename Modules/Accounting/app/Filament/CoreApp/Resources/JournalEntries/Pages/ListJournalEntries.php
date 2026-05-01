<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\Pages;

use Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\JournalEntryResource;
use Modules\Core\Support\Pages\BaseListRecords;

final class ListJournalEntries extends BaseListRecords
{
    protected static string $resource = JournalEntryResource::class;
}
