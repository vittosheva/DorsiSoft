<?php

declare(strict_types=1);

namespace Modules\People\Support\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

final class DismissDuplicatePartnerCalloutAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->icon(Heroicon::XMark)
            ->iconButton()
            ->alpineClickHandler('$set(\'hideCallout\', true)')
            ->color('gray');
    }

    public static function getDefaultName(): ?string
    {
        return 'dismiss';
    }
}
