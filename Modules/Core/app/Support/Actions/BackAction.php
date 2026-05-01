<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

final class BackAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->icon(Heroicon::OutlinedArrowLeft)
            ->color('gray')
            ->tooltip(__('Go back'));
    }

    public static function getDefaultName(): ?string
    {
        return 'back';
    }
}
