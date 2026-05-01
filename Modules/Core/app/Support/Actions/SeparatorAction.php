<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;

final class SeparatorAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->hiddenLabel()
            ->view('core::components.action-separator');
    }

    public static function getDefaultName(): ?string
    {
        return 'separator';
    }
}
