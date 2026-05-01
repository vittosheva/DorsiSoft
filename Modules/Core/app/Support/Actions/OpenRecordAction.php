<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

final class OpenRecordAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Open'))
            ->tooltip(__('Open'))
            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
            ->color('gray');
    }

    public static function getDefaultName(): ?string
    {
        return 'open_record';
    }
}
