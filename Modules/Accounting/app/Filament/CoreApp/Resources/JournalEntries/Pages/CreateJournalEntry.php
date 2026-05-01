<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\Pages;

use Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\JournalEntryResource;
use Modules\Core\Support\Pages\BaseCreateRecord;

final class CreateJournalEntry extends BaseCreateRecord
{
    protected static string $resource = JournalEntryResource::class;
}
