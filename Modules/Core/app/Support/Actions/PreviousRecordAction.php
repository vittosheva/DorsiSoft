<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

final class PreviousRecordAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->outlined()
            ->icon(Heroicon::ChevronLeft)
            ->tooltip(__('Previous'));
    }

    public static function getDefaultName(): ?string
    {
        return 'previous-record';
    }
}
