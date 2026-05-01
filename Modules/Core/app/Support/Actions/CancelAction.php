<?php

declare(strict_types=1);

namespace Modules\Core\Support\Actions;

use Filament\Actions\Action;

final class CancelAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->color('gray');
    }

    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }
}
