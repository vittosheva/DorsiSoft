<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

final class FastCreateAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->tooltip(__('Fast create'))
            ->icon(Heroicon::Bolt)
            ->color('gray')
            ->slideOver();
    }

    public static function getDefaultName(): ?string
    {
        return 'fast_create';
    }
}
