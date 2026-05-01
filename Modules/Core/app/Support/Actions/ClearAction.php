<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;

final class ClearAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('Clear'))
            ->link()
            ->color('danger')
            ->icon(Heroicon::XCircle)
            ->iconPosition(IconPosition::After);
    }

    public static function getDefaultName(): ?string
    {
        return 'clear';
    }
}
