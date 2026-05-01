<?php

declare(strict_types=1);

namespace Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\Pages;

use Filament\Actions\DeleteAction;
use Modules\Accounting\Filament\CoreApp\Resources\JournalEntries\JournalEntryResource;
use Modules\Accounting\Models\JournalEntry;
use Modules\Core\Support\Pages\BaseEditRecord;

final class EditJournalEntry extends BaseEditRecord
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => $this->getRecord() instanceof JournalEntry
                    && $this->getRecord()->isDraft()),
        ];
    }
}
