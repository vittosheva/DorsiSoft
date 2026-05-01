<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

final class NextRecordAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->outlined()
            ->icon(Heroicon::ChevronRight)
            ->tooltip(__('Next'));
    }

    public static function getDefaultName(): ?string
    {
        return 'next-record';
    }
}
